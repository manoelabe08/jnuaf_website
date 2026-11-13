<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsletterSubscriberController extends Controller
{
    
public function index(Request $request)

    {

        $sourceList = NewsletterSubscriber::query()->pluck('sources')

            ->map(fn ($value) => is_string($value) ? (json_decode($value, true) ?: []) : (array) $value)

            ->flatten()->filter()->unique()->sort()->values()->all();



        $filters = $request->validate([

            'q' => ['nullable', 'string', 'max:200'],

            'status' => ['nullable', Rule::in(['pending', 'subscribed', 'unsubscribed', 'bounced'])],

            'source' => ['nullable', Rule::in($sourceList)],

            'from' => ['nullable', 'date'],

            'to' => ['nullable', 'date'],

            'name' => ['nullable', Rule::in(['with', 'without'])],

            'sort' => ['nullable', Rule::in(['newest', 'oldest', 'email', 'name'])],

        ]);



        $query = NewsletterSubscriber::query();



        if (! empty($filters['q'])) {

            $q = $filters['q'];

            $query->where(fn ($builder) => $builder->where('email', 'like', '%'.$q.'%')->orWhere('name', 'like', '%'.$q.'%'));

        }

        if (! empty($filters['status'])) {

            $query->where('status', $filters['status']);

        }

        if (! empty($filters['source'])) {

            $query->where('sources', 'like', '%"'.$filters['source'].'"%');

        }

        if (! empty($filters['from'])) {

            $query->whereDate('created_at', '>=', $filters['from']);

        }

        if (! empty($filters['to'])) {

            $query->whereDate('created_at', '<=', $filters['to']);

        }

        if (($filters['name'] ?? '') === 'with') {

            $query->whereNotNull('name')->where('name', '!=', '');

        } elseif (($filters['name'] ?? '') === 'without') {

            $query->where(fn ($builder) => $builder->whereNull('name')->orWhere('name', ''));

        }



        match ($filters['sort'] ?? 'newest') {

            'oldest' => $query->orderBy('id'),

            'email' => $query->orderBy('email'),

            'name' => $query->orderBy('name')->orderBy('email'),

            default => $query->orderByDesc('id'),

        };



        $counts = NewsletterSubscriber::query()

            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))

            ->groupBy('status')->pluck('total', 'status');



        return view('admin.newsletter.subscribers', [

            'subscribers' => $query->paginate(30)->withQueryString(),

            'sourceList' => $sourceList,

            'counts' => $counts,

        ]);

    }


    public function export(Request $request)

    {

        $filters = $request->validate([

            'q' => ['nullable', 'string', 'max:200'],

            'status' => ['nullable', Rule::in(['pending', 'subscribed', 'unsubscribed', 'bounced'])],

            'source' => ['nullable', 'string', 'max:100'],

            'from' => ['nullable', 'date'],

            'to' => ['nullable', 'date'],

            'name' => ['nullable', Rule::in(['with', 'without'])],

        ]);

        $query = NewsletterSubscriber::query()->orderBy('id');

        if (! empty($filters['q'])) {

            $q = $filters['q'];

            $query->where(fn ($b) => $b->where('email', 'like', '%'.$q.'%')->orWhere('name', 'like', '%'.$q.'%'));

        }

        if (! empty($filters['status'])) {

            $query->where('status', $filters['status']);

        }

        if (! empty($filters['source'])) {

            $query->where('sources', 'like', '%"'.$filters['source'].'"%');

        }

        if (! empty($filters['from'])) {

            $query->whereDate('created_at', '>=', $filters['from']);

        }

        if (! empty($filters['to'])) {

            $query->whereDate('created_at', '<=', $filters['to']);

        }

        if (($filters['name'] ?? '') === 'with') {

            $query->whereNotNull('name')->where('name', '!=', '');

        } elseif (($filters['name'] ?? '') === 'without') {

            $query->where(fn ($b) => $b->whereNull('name')->orWhere('name', ''));

        }

        \Illuminate\Support\Facades\Log::info('newsletter subscribers export', ['user' => $request->user()->id, 'rows' => (clone $query)->count(), 'filters' => $filters]);

        $clean = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : (string) $v;



        return response()->streamDownload(function () use ($query, $clean) {

            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['email', 'domaine', 'nom', 'statut', 'sources', 'motif_rebond', 'date_rebond', 'inscription'], ';', '"', '');

            foreach ($query->cursor() as $s) {

                $email = (string) $s->email;

                fputcsv($out, [

                    $clean($email),

                    substr(strrchr($email, '@') ?: '', 1),

                    $clean($s->name),

                    $s->status,

                    implode(',', (array) ($s->sources ?? [])),

                    $clean($s->bounce_reason),

                    optional($s->bounced_at)->format('Y-m-d H:i'),

                    optional($s->created_at)->format('Y-m-d H:i'),

                ], ';', '"', '');

            }

            fclose($out);

        }, 'adresses-newsletter-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);

    }




    public function store(Request $request, NewsletterService $service)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:254'], 'name' => ['nullable', 'string', 'max:255'],
            'subscribe' => ['nullable', 'boolean'], 'consent_note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($request->boolean('subscribe')) { $request->validate(['confirm_consent' => ['accepted'], 'consent_note' => ['required', 'string', 'max:2000']]); }
        $service->saveContact($data['email'], $data['name'] ?? null, 'manual', $request->boolean('subscribe'), $data['consent_note'] ?? null);
        return back()->with('status', 'Adresse enregistrée. Une adresse en attente ne reçoit aucune campagne.');
    }
    public function update(Request $request, NewsletterSubscriber $subscriber)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'subscribed', 'unsubscribed', 'bounced'])],
            'consent_note' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['status'] === 'subscribed') {
            $request->validate(['confirm_consent' => ['accepted'], 'consent_note' => ['required', 'string', 'max:2000']]);
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($subscriber, $data) {
            $subscriber = NewsletterSubscriber::lockForUpdate()->findOrFail($subscriber->id);
            $subscriber->status = $data['status'];
            if ($data['status'] === 'subscribed') {
                $subscriber->consent_note = $data['consent_note'];
                $subscriber->consented_at = now(); $subscriber->unsubscribed_at = null; $subscriber->bounced_at = null; $subscriber->bounce_reason = null;
            } elseif ($data['status'] === 'unsubscribed') { $subscriber->unsubscribed_at = now(); } elseif ($data['status'] === 'bounced') { $subscriber->bounced_at = now(); $subscriber->bounce_reason = 'Marquee manuellement par la coordination'; }
            $subscriber->save();
        });
        return back()->with('status', 'Statut de l’adresse enregistré.');
    }
    public function import(Request $request, NewsletterService $service)
    {
        $data = $request->validate(['sources' => ['required', 'array', 'min:1'], 'sources.*' => [Rule::in(['users', 'registrations'])]]);
        $count = $service->importContacts($data['sources']);
        return back()->with('status', $count.' ligne(s) examinée(s). Les nouvelles adresses sont en attente ; les désinscriptions sont conservées.');
    }
}
