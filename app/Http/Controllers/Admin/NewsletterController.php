<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterAttachment;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NewsletterController extends Controller
{
    public function index()
    {
        return view('admin.newsletter.index', [
            'campaigns' => NewsletterCampaign::withCount('deliveries')->latest()->paginate(20),
            'subscribed' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            'sources' => NewsletterService::SOURCES,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:150', 'not_regex:/[\r\n]/'],
            'body' => ['required', 'string', 'max:30000'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['required', 'distinct', Rule::in(array_keys(NewsletterService::SOURCES))],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:5120', 'mimes:pdf,png,jpg,jpeg,docx,xlsx,pptx'],
        ]);
    }

    public function store(Request $request, NewsletterService $service)
    {
        $data = $this->validated($request);
        $campaign = NewsletterCampaign::create(collect($data)->only(['title', 'subject', 'body', 'sources'])->all());
        try { $service->addAttachments($campaign, $request->file('files', [])); }
        catch (\Throwable $exception) {
            $campaign->delete();
            throw $exception;
        }
        return redirect()->route('admin.newsletter.show', $campaign)->with('status', 'Brouillon enregistré. Envoyez-vous un test avant la mise en envoi.');
    }

    public function show(NewsletterCampaign $campaign, NewsletterService $service)
    {
        return view('admin.newsletter.show', [
            'campaign' => $campaign->load('attachments'), 'sources' => NewsletterService::SOURCES,
            'eligible' => $service->eligible($campaign)->count(),
            'counts' => $campaign->deliveries()->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total', 'status'),
            
'deliveries' => $deliveries = \App\Services\NewsletterEta::listing($campaign, request()),

            'eta' => \App\Services\NewsletterEta::forPage($deliveries),

        ]);
    }

    public function update(Request $request, NewsletterCampaign $campaign, NewsletterService $service)
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($campaign, $data, $request, $service) {
            $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless($campaign->status === 'draft', 409, 'La campagne envoyée ou mise en file n’est plus modifiable.');
            $campaign->update(collect($data)->only(['title', 'subject', 'body', 'sources'])->all());
            $service->addAttachments($campaign, $request->file('files', []));
        });
        return back()->with('status', 'Brouillon enregistré.');
    }

    public function preview(NewsletterCampaign $campaign)
    {
        $html = (new \App\Mail\NewsletterMessage($campaign->load('attachments')))->render();
        return response($html)->header('Cache-Control', 'private, no-store')->header('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test(NewsletterCampaign $campaign, Request $request, NewsletterService $service)
    {
        try { $service->sendTest($campaign, $request->user()->email); }
        catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['mail' => 'Échec du test : vérifiez le SMTP et le journal Laravel.']);
        }
        return back()->with('status', 'Email de test transmis au transport SMTP pour votre adresse administrateur.');
    }

    public function queue(NewsletterCampaign $campaign, Request $request, NewsletterService $service)
    {
        $request->validate(['confirm' => ['accepted']]);
        try { $count = $service->queueCampaign($campaign); }
        catch (\Illuminate\Validation\ValidationException $exception) { throw $exception; }
        catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['mail' => 'Impossible de préparer la campagne. Vérifiez le stockage privé et le SMTP.']);
        }
        return back()->with('status', $count.' destinataire(s) mis en file. Les envois seront effectués par le cron, un par un.');
    }

    
public function schedule(NewsletterCampaign $campaign, Request $request, NewsletterService $service)

    {

        $request->validate(['scheduled_at' => ['required', 'date_format:Y-m-d\TH:i']]);

        $when = $service->parseLocal((string) $request->input('scheduled_at'));



        if ($when->lte(now()->addMinutes(2))) {

            return back()->withErrors(['scheduled_at' => 'Choisissez une date et une heure dans le futur (au moins 2 minutes).'])->withInput();

        }

        if ($service->eligible($campaign)->count() === 0) {

            return back()->withErrors(['scheduled_at' => 'Aucun abonné actif pour les sources choisies.'])->withInput();

        }



        $updated = NewsletterCampaign::whereKey($campaign->id)->where('status', 'draft')

            ->update(['status' => 'scheduled', 'scheduled_at' => $when->format('Y-m-d H:i:s')]);

        abort_if($updated !== 1, 409, 'Cette campagne n’est plus un brouillon.');



        $local = $when->copy()->setTimezone($service->displayTimezone());



        return back()->with('status', 'Newsletter programmée pour le '.$local->format('d/m/Y à H:i').' ('.$service->displayTimezone().').');

    }



    public function unschedule(NewsletterCampaign $campaign)

    {

        $updated = NewsletterCampaign::whereKey($campaign->id)->where('status', 'scheduled')

            ->update(['status' => 'draft', 'scheduled_at' => null]);



        return back()->with('status', $updated

            ? 'Programmation annulée : la campagne est redevenue un brouillon modifiable.'

            : 'Trop tard : l’envoi a déjà démarré.');

    }



    public function cancel(NewsletterCampaign $campaign)

    {
        DB::transaction(function () use ($campaign) {
            $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            if (! in_array($campaign->status, ['queued', 'sending'], true)) { return; }
            $campaign->update(['status' => 'cancelled', 'finished_at' => now()]);
            $campaign->deliveries()->where('status', 'pending')->update(['status' => 'skipped', 'error' => 'Campagne arrêtée.']);
        });
        return back()->with('status', 'Envois non commencés arrêtés. Un envoi déjà en cours peut se terminer.');
    }

    public function retry(NewsletterCampaign $campaign, Request $request)
    {
        $request->validate(['confirm_retry' => ['accepted']]);
        DB::transaction(function () use ($campaign) {
            $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            abort_if($campaign->status === 'draft' || $campaign->web_expires_at?->isPast(), 409);
            $count = $campaign->deliveries()->whereIn('status', ['failed', 'uncertain'])
                ->update(['status' => 'pending', 'processing_at' => null, 'error' => null]);
            if ($count) { $campaign->update(['status' => 'queued', 'finished_at' => null]); }
        });
        return back()->with('status', 'Envois échoués/incertains remis en file. Les messages déjà marqués transmis ne sont pas relancés.');
    }


    public function force(NewsletterCampaign $campaign, Request $request)

    {

        abort_unless(in_array($campaign->status, ['queued', 'sending'], true), 404);

        $data = $request->validate([

            'count' => ['required', 'integer', 'min:1', 'max:500'],

            'confirm_force' => ['accepted'],

        ]);

        $pending = $campaign->deliveries()->where('status', 'pending')->count();

        $todo = min((int) $data['count'], $pending);

        if ($todo < 1) {

            return back()->with('status', 'Aucun envoi en attente a forcer.');

        }

        \Illuminate\Support\Facades\Cache::forget('jnuaf-newsletter-cooldown');

        \Illuminate\Support\Facades\Cache::put('jnuaf-newsletter-force', $todo, now()->addHours(6));

        \Illuminate\Support\Facades\Log::info('newsletter force via interface', ['campaign' => $campaign->id, 'count' => $todo, 'user' => $request->user()->id]);



        return back()->with('status', 'Forcage actif : '.$todo.' envoi(s) partiront dans les prochaines minutes.');

    }





    public function prioritize(NewsletterCampaign $campaign, NewsletterDelivery $delivery)

    {

        abort_unless((int) $delivery->newsletter_campaign_id === (int) $campaign->id, 404);

        abort_unless(in_array($campaign->status, ['queued', 'sending'], true) && $delivery->status === 'pending', 404);

        $delivery->forceFill(['prioritized_at' => now()])->save();

        \Illuminate\Support\Facades\Log::info('newsletter delivery prioritized', ['campaign' => $campaign->id, 'delivery' => $delivery->id, 'user' => auth()->id()]);



        return back()->with('status', 'Envoi place en tete de file.');

    }




    public function file(NewsletterCampaign $campaign, NewsletterAttachment $attachment, NewsletterService $service)
    {
        abort_unless($attachment->newsletter_campaign_id == $campaign->id, 404);
        return response()->download($service->attachmentPath($attachment), $attachment->download_name, [
            'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function deleteFile(NewsletterCampaign $campaign, NewsletterAttachment $attachment, NewsletterService $service)
    {
        $path = null;
        DB::transaction(function () use ($campaign, $attachment, $service, &$path) {
            $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            abort_unless($campaign->status === 'draft' && $attachment->newsletter_campaign_id == $campaign->id, 409);
            $path = $service->attachmentPath($attachment);
            $attachment->delete();
        });
        if ($path && is_file($path)) { @unlink($path); }
        return back()->with('status', 'Pièce jointe retirée du brouillon.');
    }
}
