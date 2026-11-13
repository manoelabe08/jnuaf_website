<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Event;

use App\Models\ExportLog;

use App\Models\Registration;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Str;

use Illuminate\View\View;

use Symfony\Component\HttpFoundation\StreamedResponse;



class EventRegistrationController extends Controller

{

    public function index(Request $request, Event $event): View

    {

        $this->authorizeAdmin($request);



        $base = Registration::where('event_id', $event->id);



        return view('admin.events.registrations', [

            'event' => $event->load('territory'),

            'registrations' => $this->filtered($request, $event)->with('territory')->latest()->paginate(25)->withQueryString(),

            'filters' => $request->only(['q', 'status', 'presence']),

            'stats' => [

                'confirmed' => (clone $base)->where('status', 'confirmed')->count(),

                'waitlisted' => (clone $base)->where('status', 'waitlisted')->count(),

                'cancelled' => (clone $base)->where('status', 'cancelled')->count(),

                'present' => (clone $base)->whereNotNull('checked_in_at')->count(),

            ],

            'pending' => (clone $base)->whereNotNull('checked_in_at')->whereNull('attestation_sent_at')->whereNull('anonymized_at')->count(),

        ]);

    }



    public function export(Request $request, Event $event): StreamedResponse

    {

        $this->authorizeAdmin($request);



        $query = $this->filtered($request, $event)->with('territory')->orderBy('id');



        ExportLog::create([

            'user_id' => $request->user()->id,

            'type' => 'registrations',

            'filters' => array_filter(array_merge(['event' => $event->id], $request->only(['q', 'status', 'presence']))),

            'row_count' => (clone $query)->count(),

            'ip_address' => $request->ip(),

        ]);



        $filename = 'inscrits-' . Str::slug($event->title) . '-' . now()->format('Ymd-His') . '.csv';



        return response()->streamDownload(function () use ($query) {

            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['Badge', 'Profil', 'Statut', 'Nom', "Pr\u{e9}nom", 'Email', "T\u{e9}l\u{e9}phone", 'Territoire', 'Ville', "Accessibilit\u{e9}", "Droit \u{e0} l'image", 'Inscrit(e) le', "Pr\u{e9}sent(e) le", "Attestation envoy\u{e9}e le"], ';');



            $query->chunk(500, function ($rows) use ($out) {

                foreach ($rows as $r) {

                    fputcsv($out, array_map(fn ($v) => $this->cell($v), [

                        $r->badge_code, $r->profile->label(), $r->status->label(), $r->last_name, $r->first_name,

                        $r->email, $r->phone, $r->territory->city, $r->city, $r->accessibility_needs,

                        $r->consent_image ? 'oui' : 'non', $r->created_at->format('d/m/Y H:i'),

                        $r->checked_in_at?->format('d/m/Y H:i'), $r->attestation_sent_at?->format('d/m/Y H:i'),

                    ]), ';');

                }

            });



            fclose($out);

        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);

    }



    public function sendAttestations(Request $request, Event $event): RedirectResponse

    {

        $this->authorizeAdmin($request);

        $request->validate(['confirm' => ['accepted']]);

        abort_if($event->starts_at && $event->starts_at->isFuture(), 422, "L'\u{e9}v\u{e9}nement n'a pas encore eu lieu.");



        Artisan::call('jnuaf:send-attestations', ['--event' => $event->id]);



        return back()->with('status', trim(Artisan::output()));

    }



    private function filtered(Request $request, Event $event): Builder

    {

        return Registration::where('event_id', $event->id)

            ->when($request->filled('q'), function (Builder $q) use ($request) {

                $term = '%' . $request->string('q')->trim() . '%';

                $q->where(fn (Builder $w) => $w->where('last_name', 'like', $term)->orWhere('first_name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('badge_code', 'like', $term));

            })

            ->when(in_array($request->input('status'), ['confirmed', 'waitlisted', 'cancelled'], true), fn (Builder $q) => $q->where('status', $request->input('status')))

            ->when($request->input('presence') === 'present', fn (Builder $q) => $q->whereNotNull('checked_in_at'))

            ->when($request->input('presence') === 'absent', fn (Builder $q) => $q->whereNull('checked_in_at'));

    }



    private function cell(mixed $v): mixed

    {

        if (is_string($v) && $v !== '' && preg_match('/^[=+\-@\t\r]/', $v) && ! preg_match('/^\+?[0-9 ().\-]+$/', $v)) {

            return "'" . $v;

        }



        return $v;

    }



    private function authorizeAdmin(Request $request): void

    {

        abort_unless($request->user()->isAdmin(), 403);

    }

}

