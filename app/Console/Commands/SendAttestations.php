<?php

namespace App\Console\Commands;

use App\Mail\AttestationAvailable;

use App\Models\Registration;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Mail;

/**

 * A lancer apres l'evenement : envoie l'attestation PDF a chaque personne pointee a l'entree.

 * Idempotente : une personne ne recoit son attestation qu'une fois.

 */

class SendAttestations extends Command

{

    protected $signature = "jnuaf:send-attestations {--territory= : Code du territoire (ex. ABJ) pour n'envoyer qu'\u{e0} celui-ci} {--event= : Identifiant d'un \u{e9}v\u{e9}nement (formation, rencontre...)}";

    protected $description = "Envoie les attestations de participation aux personnes pr\u{e9}sentes";

    public function handle(): int

    {

        $sent = 0;

        $query = $this->option('event')

            ? Registration::where('event_id', (int) $this->option('event'))

            : Registration::currentEdition();

        $query->whereNotNull('checked_in_at')

            ->whereNull('attestation_sent_at')

            ->whereNull('anonymized_at')

            ->when($this->option('territory'), fn ($q, $code) => $q->whereHas('territory', fn ($t) => $t->where('code', strtoupper($code))))

            ->with('territory')

            ->chunkById(100, function ($registrations) use (&$sent) {

                foreach ($registrations as $registration) {

                    Mail::to($registration->email)->send(new AttestationAvailable($registration));

                    $registration->forceFill(['attestation_sent_at' => now()])->save();

                    $sent++;

                }

            });

        $this->info("{$sent} attestation(s) envoy\u{e9}e(s).");

        return self::SUCCESS;

    }

}

