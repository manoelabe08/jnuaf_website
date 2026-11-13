<?php

namespace App\Console\Commands;

use App\Enums\ApplicationStatus;
use App\Models\CommitteeApplication;
use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Applique les durées de conservation annoncées dans la politique de confidentialité :
 *  - inscriptions anonymisées N mois après l'événement (profil, territoire, présence conservés
 *    pour les statistiques ; nom, coordonnées et textes libres effacés) ;
 *  - candidatures au comité supprimées (avec CV et lettre) N mois après la décision,
 *    ou 12 mois après le dépôt si elles n'ont jamais été traitées.
 */
class PurgePersonalData extends Command
{
    protected $signature = 'jnuaf:purge-data {--dry-run : Affiche ce qui serait traité sans rien modifier}';

    protected $description = 'Anonymise les inscriptions et supprime les candidatures arrivées en fin de durée de conservation';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $retention = (int) config('jnuaf.privacy.retention_months');
        $committeeRetention = (int) config('jnuaf.privacy.committee_retention_months');

        $registrations = $this->expiredRegistrations($retention);
        $applications = $this->expiredApplications($committeeRetention);

        $this->info(sprintf('%s%d inscription(s) à anonymiser, %d candidature(s) à supprimer.',
            $dry ? '[simulation] ' : '', $registrations->count(), $applications->count()));

        if ($dry) {
            return self::SUCCESS;
        }

        $registrations->chunkById(200, function ($chunk) {
            foreach ($chunk as $registration) {
                $this->anonymize($registration);
            }
        });

        // Suppression modèle par modèle : l'événement « deleted » efface CV et lettre du disque.
        $applications->chunkById(100, fn ($chunk) => $chunk->each->delete());

        return self::SUCCESS;
    }

    private function expiredRegistrations(int $months): Builder
    {
        $currentEventExpired = now()->greaterThan(Carbon::parse(config('jnuaf.event_date'))->addMonths($months));

        return Registration::query()
            ->whereNull('anonymized_at')
            ->where(function (Builder $q) use ($months, $currentEventExpired) {
                // Édition en cours : à partir de la date de l'événement.
                $q->when($currentEventExpired, fn (Builder $w) => $w->orWhere('edition', config('jnuaf.edition')))
                    // Éditions passées (date non conservée) : l'événement a lieu au plus tard
                    // 12 mois après l'inscription.
                    ->orWhere(fn (Builder $w) => $w->where('edition', '!=', config('jnuaf.edition'))
                        ->where('created_at', '<', now()->subMonths($months + 12)));
            });
    }

    private function expiredApplications(int $months): Builder
    {
        return CommitteeApplication::query()->where(function (Builder $q) use ($months) {
            $q->where(fn (Builder $w) => $w
                ->whereIn('status', [ApplicationStatus::Accepted, ApplicationStatus::Rejected])
                ->where('reviewed_at', '<', now()->subMonths($months)))
                ->orWhere('created_at', '<', now()->subMonths(12));
        });
    }

    private function anonymize(Registration $registration): void
    {
        app(\App\Services\PhotoCollectionService::class)->deleteRegistrationFiles($registration);
        $registration->forceFill([
            'last_name' => 'Anonyme',
            'first_name' => 'Anonyme',
            'email' => "anonyme-{$registration->id}@anonymise.invalid",
            'phone' => '',
            'city' => '',
            'organization' => null,
            'motivation' => null,
            'skills' => null,
            'availability' => null,
            'experience' => null,
            'expertise' => null,
            'bio' => null,
            'topic' => null,
            'accessibility_needs' => null,
            'consent_image' => false,
            // Badge et lien personnel rendus inutilisables.
            'qr_token' => Str::random(40),
            'manage_token_hash' => hash('sha256', Str::random(64)),
            'anonymized_at' => now(),
        ])->save();
    }
}
