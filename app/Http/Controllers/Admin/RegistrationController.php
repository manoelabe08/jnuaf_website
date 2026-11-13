<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Profile;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use App\Models\Registration;
use App\Models\Territory;
use App\Services\BadgeService;
use App\Services\RegistrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.registrations.index', [
            'registrations' => $this->filtered($request)->with('territory')->latest()->paginate(25)->withQueryString(),
            'territories' => $this->territoriesFor($request),
            'profiles' => Profile::options(),
            'filters' => $request->only(['q', 'territory', 'profile', 'status', 'presence']),
        ]);
    }

    public function show(Request $request, Registration $registration): View
    {
        $this->authorizeAccess($request, $registration);

        return view('admin.registrations.show', ['registration' => $registration->load('territory', 'checkedInBy')]);
    }

    public function update(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $this->authorizeAccess($request, $registration);

        $data = $request->validate(['status' => ['required', Rule::enum(RegistrationStatus::class)]]);
        // Confirmer envoie le badge ; libérer une place confirmée promeut la liste d'attente.
        $service->changeStatus($registration, RegistrationStatus::from($data['status']));

        return back()->with('status', 'Statut mis à jour.');
    }

    public function resendBadge(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $this->authorizeAccess($request, $registration);
        abort_unless($registration->status === RegistrationStatus::Confirmed, 422, 'Seule une inscription confirmée a un badge.');

        $service->sendConfirmation($registration);

        return back()->with('status', 'Badge renvoyé à '.$registration->email.'.');
    }

    public function badge(Request $request, Registration $registration, BadgeService $badges): Response
    {
        $this->authorizeAccess($request, $registration);

        return response($badges->pdf($registration), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$badges->filename($registration).'"',
        ]);
    }

    public function attestation(Request $request, Registration $registration, BadgeService $pdf): Response
    {
        $this->authorizeAccess($request, $registration);
        abort_unless($registration->canGetAttestation(), 403, 'Personne non pointée à l\'entrée.');

        return response($pdf->attestationPdf($registration), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$pdf->attestationFilename($registration).'"',
        ]);
    }

    /** Équivalent web de « php artisan jnuaf:send-attestations » (hébergement sans terminal). */
    public function sendAttestations(): RedirectResponse
    {
        Artisan::call('jnuaf:send-attestations');

        return back()->with('status', trim(Artisan::output()));
    }

    public function destroy(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $this->authorizeAccess($request, $registration);
        $service->delete($registration);

        return redirect()->route('admin.registrations.index')->with('status', 'Inscription supprimée définitivement.');
    }

    /** Export CSV des inscriptions filtrées, journalisé (qui, quand, quels filtres, combien de lignes). */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->filtered($request)->with('territory')->orderBy('id');

        ExportLog::create([
            'user_id' => $request->user()->id,
            'type' => 'registrations',
            'filters' => array_filter($request->only(['q', 'territory', 'profile', 'status', 'presence'])),
            'row_count' => (clone $query)->count(),
            'ip_address' => $request->ip(),
        ]);

        $filename = 'inscriptions-jnuaf-'.config('jnuaf.edition').'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM pour l'ouverture correcte dans Excel
            fputcsv($out, ['Badge', 'Profil', 'Statut', 'Nom', 'Prénom', 'Email', 'Téléphone', 'Territoire', 'Ville',
                'Structure', 'Compétences', 'Disponibilités', 'Expertise', 'Thématique', 'Accessibilité',
                'Droit à l\'image', 'Inscrite le', 'Présente le'], ';');

            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $r) {
                    fputcsv($out, [
                        $r->badge_code, $r->profile->label(), $r->status->label(), $r->last_name, $r->first_name,
                        $r->email, $r->phone, $r->territory->city, $r->city, $r->organization, $r->skills,
                        $r->availability, $r->expertise, $r->topic, $r->accessibility_needs,
                        $r->consent_image ? 'oui' : 'non', $r->created_at->format('d/m/Y H:i'),
                        $r->checked_in_at?->format('d/m/Y H:i'),
                    ], ';');
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtered(Request $request): Builder
    {
        $user = $request->user();

        return Registration::currentEdition()->visibleTo($user)
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = '%'.$request->string('q')->trim().'%';
                $q->where(fn (Builder $w) => $w->where('last_name', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('badge_code', 'like', $term));
            })
            ->when($user->isAdmin() && $request->filled('territory'), fn (Builder $q) => $q->where('territory_id', $request->integer('territory')))
            ->when($request->filled('profile'), fn (Builder $q) => $q->where('profile', $request->string('profile')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->string('status')))
            ->when($request->input('presence') === 'present', fn (Builder $q) => $q->whereNotNull('checked_in_at'))
            ->when($request->input('presence') === 'absent', fn (Builder $q) => $q->whereNull('checked_in_at'));
    }

    private function territoriesFor(Request $request)
    {
        return $request->user()->isAdmin() ? Territory::active()->get() : collect();
    }

    private function authorizeAccess(Request $request, Registration $registration): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $registration->territory_id === $user->territory_id, 403);
    }
}
