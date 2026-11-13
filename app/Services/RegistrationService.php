<?php

namespace App\Services;

use App\Enums\Profile;
use App\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmed;
use App\Mail\RegistrationWaitlisted;
use App\Models\Registration;
use App\Models\Territory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class RegistrationService
{
    /** Champs propres à chaque groupe de profil ; les autres sont ignorés à l'enregistrement. */
    private const BRANCH_FIELDS = [
        'participante' => ['organization', 'motivation'],
        'benevole' => ['skills', 'availability', 'experience'],
        'speaker' => ['expertise', 'bio', 'topic'],
    ];

    /**
     * Crée l'inscription (confirmée, ou en liste d'attente si le territoire est complet),
     * génère badge et jetons, puis envoie l'email correspondant.
     * Le jeton de gestion brut est renvoyé une seule fois : seul son hash est stocké.
     *
     * @return array{0: Registration, 1: string}
     */
    public function register(array $data): array
    {
        $profile = Profile::from($data['profile']);
        $manageToken = Str::random(48);

        $branchFields = self::BRANCH_FIELDS[$profile->branch()];
        $allBranchFields = array_merge(...array_values(self::BRANCH_FIELDS));
        $payload = Arr::except($data, array_diff($allBranchFields, $branchFields));

        $registration = DB::transaction(function () use ($data, $payload, $profile, $branchFields, $manageToken) {
            // Verrou sur le territoire : deux inscriptions simultanées ne peuvent pas prendre la même dernière place.
            $territory = Territory::lockForUpdate()->findOrFail($data['territory_id']);
            $eventId = \App\Models\Event::where('type', 'jnuaf')->where('territory_id', $territory->id)->orderBy('id')->value('id');

            return Registration::create([
                ...Arr::only($payload, [
                    'last_name', 'first_name', 'phone', 'territory_id', 'city', 'accessibility_needs',
                    ...$branchFields,
                ]),
                'email' => Str::lower(trim($data['email'])),
                'edition' => config('jnuaf.edition'),
                'event_id' => $eventId,
                'profile' => $profile,
                'status' => $territory->isFull() ? RegistrationStatus::Waitlisted : RegistrationStatus::Confirmed,
                'consent_data' => true,
                'consent_image' => (bool) ($data['consent_image'] ?? false),
                'consented_at' => now(),
                'badge_code' => $this->uniqueBadgeCode($territory),
                'qr_token' => Str::random(40),
                'manage_token_hash' => Registration::hashToken($manageToken),
            ]);
        });

        if ($registration->status === RegistrationStatus::Waitlisted) {
            Mail::to($registration->email)->send(new RegistrationWaitlisted($registration, $manageToken));
        } else {
            $this->sendConfirmation($registration, $manageToken);
        }

        return [$registration, $manageToken];
    }

    public function sendConfirmation(Registration $registration, ?string $manageToken = null, bool $promoted = false): void
    {
        // Pour un renvoi depuis l'administration, un nouveau lien personnel est émis (l'ancien devient invalide).
        if ($manageToken === null) {
            $manageToken = Str::random(48);
            $registration->forceFill(['manage_token_hash' => Registration::hashToken($manageToken)]);
        }

        Mail::to($registration->email)->send(new RegistrationConfirmed($registration, $manageToken, $promoted));

        $registration->forceFill(['badge_sent_at' => now()])->save();
    }

    /** Change le statut en appliquant ses conséquences (badge, place libérée, promotion). */
    public function changeStatus(Registration $registration, RegistrationStatus $status): void
    {
        $previous = $registration->status;

        if ($previous === $status) {
            return;
        }

        $registration->update(['status' => $status]);

        if ($status === RegistrationStatus::Confirmed) {
            $this->sendConfirmation($registration);
        }

        if ($previous === RegistrationStatus::Confirmed) {
            $registration->isEventOnly() ? app(EventWaitlistService::class)->promoteNext($registration->event) : $this->promoteNext($registration->territory);
        }
    }

    public function cancel(Registration $registration): void
    {
        $this->changeStatus($registration, RegistrationStatus::Cancelled);
    }

    public function delete(Registration $registration): void
    {
        $freedSeat = $registration->status === RegistrationStatus::Confirmed;
        $territory = $registration->territory;
        $eventOnly = $registration->isEventOnly() ? $registration->event : null;

        $registration->delete();

        if ($freedSeat) {
            $eventOnly ? app(EventWaitlistService::class)->promoteNext($eventOnly) : $this->promoteNext($territory);
        }
    }

    /** Une place s'est libérée : la première personne en liste d'attente est confirmée et reçoit son badge. */
    public function promoteNext(Territory $territory): ?Registration
    {
        $next = DB::transaction(function () use ($territory) {
            $territory = Territory::lockForUpdate()->find($territory->id);

            if (! $territory || $territory->isFull()) {
                return null;
            }

            $next = Registration::where('territory_id', $territory->id)
                ->where('edition', config('jnuaf.edition'))
                ->where('status', RegistrationStatus::Waitlisted)
                ->oldest('id')
                ->lockForUpdate()
                ->first();

            $next?->update(['status' => RegistrationStatus::Confirmed]);

            return $next;
        });

        if ($next) {
            $this->sendConfirmation($next, promoted: true);
        }

        return $next;
    }

    private function uniqueBadgeCode(Territory $territory): string
    {
        $year = substr((string) config('jnuaf.edition'), -2);

        do {
            $code = sprintf('JNUAF%s-%s-%s', $year, $territory->code, Str::upper(Str::random(6)));
        } while (Registration::where('badge_code', $code)->exists());

        return $code;
    }
}
