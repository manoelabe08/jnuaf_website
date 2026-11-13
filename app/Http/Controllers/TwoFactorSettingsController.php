<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TwoFactorSettingsController extends Controller
{
    private function confirm(Request $request): void
    {
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.required' => 'Saisissez votre mot de passe pour confirmer.',
            'current_password.current_password' => 'Le mot de passe est incorrect.',
        ]);
    }

    public function enable(Request $request, TwoFactorService $service): RedirectResponse
    {
        $this->confirm($request);
        $user = $request->user();

        if ($user->two_factor_enabled) {
            return back()->with('status', 'La double authentification est déjà activée.');
        }

        $user->forceFill(['two_factor_enabled' => true])->save();
        $codes = $service->generateRecoveryCodes($user);
        Log::info('Double authentification activée', ['user_id' => $user->id]);

        return back()->with('status', 'Double authentification activée. Conservez vos codes de secours.')->with('recovery_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->confirm($request);
        $user = $request->user();

        abort_if($user->isAdmin() && config('jnuaf_security.two_factor_admin_required', true), 422, 'La double authentification est obligatoire pour les comptes à accès complet.');

        $user->forceFill(['two_factor_enabled' => false, 'two_factor_recovery' => null])->save();
        app(\App\Services\SessionService::class)->endOthers($user, $request->session()->getId());
        Log::info('Double authentification désactivée', ['user_id' => $user->id]);

        return back()->with('status', 'Double authentification désactivée.');
    }

    public function recoveryCodes(Request $request, TwoFactorService $service): RedirectResponse
    {
        $this->confirm($request);
        $user = $request->user();

        $codes = $service->generateRecoveryCodes($user);
        Log::info('Codes de secours régénérés', ['user_id' => $user->id]);

        return back()->with('status', 'Nouveaux codes de secours générés : les anciens ne fonctionnent plus.')->with('recovery_codes', $codes);
    }
}
