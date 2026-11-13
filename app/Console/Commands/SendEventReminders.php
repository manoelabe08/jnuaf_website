<?php

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;

use App\Mail\EventReminder;

use App\Models\Registration;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command

{

    protected $signature = "jnuaf:send-reminders {--force : Envoyer m\u{ea}me hors de la fen\u{ea}tre de rappel}";

    protected $description = "Envoie le rappel (avec le badge) aux inscrites confirm\u{e9}es quelques jours avant l'\u{e9}v\u{e9}nement";

    public function handle(): int

    {

        $days = (int) config('jnuaf.reminder_days_before');

        $force = (bool) $this->option('force');

        $sent = 0;

        $skipped = 0;

        // Le marquage reminder_sent_at rend la commande idempotente : relancer ne renvoie rien en double.

        Registration::query()

            ->where('status', RegistrationStatus::Confirmed)

            ->whereNull('reminder_sent_at')

            ->whereNull('anonymized_at')

            ->where(fn ($q) => $q->whereNotNull('event_id')->orWhere('edition', config('jnuaf.edition')))

            ->with('event')

            ->chunkById(200, function ($registrations) use (&$sent, &$skipped, $days, $force) {

                foreach ($registrations as $registration) {

                    $date = $registration->eventDate()->copy()->startOfDay();

                    if (! $force && ! now()->between($date->copy()->subDays($days), $date->copy()->endOfDay())) {

                        $skipped++;

                        continue;

                    }

                    Mail::to($registration->email)->send(new EventReminder($registration));

                    $registration->forceFill(['reminder_sent_at' => now()])->save();

                    $sent++;

                }

            });

        $this->info("{$sent} rappel(s) envoy\u{e9}(s), {$skipped} hors fen\u{ea}tre.");

        return self::SUCCESS;

    }

}

