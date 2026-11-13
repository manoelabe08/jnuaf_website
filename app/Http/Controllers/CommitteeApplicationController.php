<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommitteeApplicationRequest;
use App\Mail\AdminAlert;
use App\Mail\CommitteeApplicationReceived;
use App\Services\AdminNotifier;
use App\Models\CommitteeApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class CommitteeApplicationController extends Controller
{
    public function store(StoreCommitteeApplicationRequest $request, AdminNotifier $notifier): RedirectResponse
    {
        $data = $request->validated();

        // Disque privé (storage/app/private) : les pièces ne sont accessibles que via l'administration.
        $application = CommitteeApplication::create([
            'full_name' => $data['full_name'],
            'email' => mb_strtolower($data['email']),
            'phone' => $data['phone'],
            'country' => $data['country'],
            'pole' => $data['pole'],
            'cv_path' => $request->file('cv')->store('committee', 'local'),
            'letter_path' => $request->file('letter')->store('committee', 'local'),
            'consented_at' => now(),
        ]);

        $this->storePhoto($request, $application);


        if ($request->boolean('photo_public')) {

            $application->forceFill(['photo_public_consent_at' => now()])->save();

        }


        Mail::to($application->email)->send(new CommitteeApplicationReceived($application));

        $notifier->send(new AdminAlert(
            title: 'nouvelle candidature au comité',
            intro: "{$application->full_name} propose sa candidature au comité d'organisation.",
            details: [
                'Pôle' => $application->pole,
                'Pays' => $application->country,
                'Email' => $application->email,
                'WhatsApp' => $application->phone,
            ],
            url: route('admin.committee.show', $application),
        ));

        return redirect()->to(route('home').'#candidature')
            ->with('committee', ['pole' => $application->pole]);
    }



    private function storePhoto(StoreCommitteeApplicationRequest $request, CommitteeApplication $application): void

    {

        try {

            $svc = app(\App\Services\PhotoCollectionService::class);

            $tmp = $svc->normalize($request->file('photo'));

            $dir = $svc->committeeRoot();

            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {

                throw new \RuntimeException('Dossier des photos inaccessible.');

            }

            $name = $application->id.'-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(24)).'.jpg';

            copy($tmp, $dir.'/'.$name);

            chmod($dir.'/'.$name, 0600);

            @unlink($tmp);

            $application->forceFill(['photo_path' => $name, 'photo_consented_at' => now()])->save();

        } catch (\Throwable $exception) {

            report($exception);

        }

    }

}
