<?php

namespace App\Http\Controllers;

use App\Enums\RegistrationStatus;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\Registration;
use App\Services\BadgeService;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function store(StoreRegistrationRequest $request, RegistrationService $service): RedirectResponse
    {
        abort_unless(config('jnuaf.registrations_open'), 403, 'Les inscriptions sont fermées.');

        [$registration] = $service->register($request->safe()->except(['photo', 'cv']));
        $this->storeSpeakerFiles($request, $registration);

        return redirect()->to(route('home').'#inscription')->with('registration', [
            'first_name' => $registration->first_name,
            'badge_code' => $registration->badge_code,
            'email' => $registration->email,
            'waitlisted' => $registration->status === RegistrationStatus::Waitlisted,
            'city' => $registration->territory->city,
        ]);
    }

    /** Espace personnel accessible uniquement via le lien reçu par email. */
    public function manage(string $token): View
    {
        $registration = $this->resolve($token);

        return view('registration.manage', compact('registration', 'token'));
    }

    public function badge(string $token, BadgeService $badges): Response
    {
        $registration = $this->resolve($token);
        abort_unless($registration->status === RegistrationStatus::Confirmed, 410, $registration->isCancelled()
            ? 'Cette inscription a été annulée.'
            : 'Votre badge sera disponible dès qu\'une place vous sera attribuée.');

        return response($badges->pdf($registration), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$badges->filename($registration).'"',
        ]);
    }

    public function attestation(string $token, BadgeService $pdf): Response
    {
        $registration = $this->resolve($token);
        abort_unless($registration->canGetAttestation(), 403, 'L\'attestation est délivrée aux personnes présentes le jour de l\'événement.');

        return response($pdf->attestationPdf($registration), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$pdf->attestationFilename($registration).'"',
        ]);
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $registration = $this->resolve($token);

        $data = $request->validate([
            'phone' => ['required', 'regex:/^\+?[0-9 ().-]{8,20}$/'],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'accessibility_needs' => ['nullable', 'string', 'max:255'],
            'consent_image' => ['nullable', 'boolean'],
        ]);
        $data['consent_image'] = $request->boolean('consent_image');

        $registration->update($data);

        return back()->with('status', 'Vos informations ont été mises à jour.');
    }

    public function cancel(string $token, RegistrationService $service): RedirectResponse
    {
        $service->cancel($this->resolve($token));

        return back()->with('status', 'Votre inscription est annulée. Votre badge n\'est plus valide.');
    }

    /** Droit à l'effacement : suppression définitive des données. */
    public function destroy(string $token, RegistrationService $service): RedirectResponse
    {
        $service->delete($this->resolve($token));

        return redirect()->route('home')->with('flash', 'Vos données ont été supprimées définitivement.');
    }


    private function storeSpeakerFiles(StoreRegistrationRequest $request, Registration $registration): void

    {

        if (\App\Enums\Profile::tryFrom((string) $request->input('profile'))?->branch() !== 'speaker') {

            return;

        }

        if (! $request->hasFile('photo') && ! $request->hasFile('cv')) {

            return;

        }

        try {

            $svc = app(\App\Services\PhotoCollectionService::class);

            $updates = [];

            if ($request->hasFile('photo')) {

                $tmp = $svc->normalize($request->file('photo'));

                $updates['photo_path'] = $svc->storeRegistrationPhoto($registration, $tmp);

                @unlink($tmp);

            }

            if ($request->hasFile('cv')) {

                $updates['cv_path'] = $svc->storeRegistrationCv($registration, $request->file('cv'));

            }

            $registration->forceFill($updates)->save();

        } catch (\Throwable $exception) {

            report($exception);

        }

    }

    private function resolve(string $token): Registration
    {
        return Registration::findByManageToken($token) ?? abort(404);
    }
}
