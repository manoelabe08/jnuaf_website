<?php

namespace App\Console\Commands;

use App\Mail\TemporaryPasswordReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RemindExpiringPasswords extends Command
{
    protected $signature = 'accounts:remind-expiring-passwords';
    protected $description = 'Prévient les utilisateurs dont le mot de passe temporaire expire dans moins de 48 heures';

    public function handle(): int
    {
        $count = 0;

        User::where('must_change_password', true)
            ->whereNotNull('password_expires_at')
            ->where('password_expires_at', '>', now())
            ->where('password_expires_at', '<=', now()->addHours(48))
            ->whereNull('expiry_reminded_at')
            ->get()
            ->each(function (User $user) use (&$count) {
                try {
                    Mail::to($user->email)->send(new TemporaryPasswordReminder($user));
                    $user->forceFill(['expiry_reminded_at' => now()])->save();
                    $count++;
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });

        $this->info($count.' rappel(s) envoyé(s).');

        return self::SUCCESS;
    }
}
