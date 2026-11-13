<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;

class ResetTwoFactor extends Command
{
    protected $signature = 'user:reset-2fa {email}';
    protected $description = 'Secours : supprime les codes en attente et les codes de secours d’un compte';

    public function handle(TwoFactorService $service): int
    {
        $user = User::where('email', mb_strtolower($this->argument('email')))->first();
        if (! $user) {
            $this->error('Compte introuvable.');

            return self::FAILURE;
        }

        $service->clear($user);
        $user->forceFill(['two_factor_enabled' => false, 'two_factor_recovery' => null])->save();
        $this->info('Double authentification réinitialisée pour '.$user->email.'.');

        return self::SUCCESS;
    }
}
