<?php

namespace App\Services;

use App\Enums\Role;
use App\Mail\AdminAlert;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminNotifier
{
    /** Comptes administrateurs + adresse supplémentaire définie dans les paramètres. */
    public function recipients(): array
    {
        $central = mb_strtolower(trim((string) config('jnuaf.notify_email'))); if ($central !== '') { return [$central]; } return User::where('role', Role::Admin)->pluck('email')
            ->push(config('jnuaf.notify_email'))
            ->filter()->map(fn ($e) => mb_strtolower($e))->unique()->values()->all();
    }

    public function send(AdminAlert $alert): void
    {
        $recipients = $this->recipients();

        if ($recipients === []) {
            return;
        }

        // Une alerte en échec ne doit jamais faire échouer l'envoi du formulaire public.
        try {
            Mail::to($recipients)->send($alert);
        } catch (\Throwable $e) {
            Log::error('Alerte admin non envoyée : '.$e->getMessage());
        }
    }
}
