<?php

namespace App\Http\Controllers;

use App\Mail\AccountAudit;
use App\Services\TotpService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TotpSettingsController extends Controller
{
    private function confirmPassword(Request $request): void
    {
        $request->validate(['current_password' => ['required', 'current_password']], [
            'current_password.required' => 'Saisissez votre mot de passe pour confirmer.',
            'current_password.current_password' => 'Le mot de passe est incorrect.',
        ]);
    }

    public function start(Request $request, TotpService $totp): RedirectResponse
    {
        $this->confirmPassword($request);

        if ($request->user()->hasTotp()) {
            return back()->with('status', 'Une application d’authentification est déjà configurée.');
        }

        $request->session()->put('totp_pending', Crypt::encryptString($totp->generateSecret()));

        return back()->with('status', 'Scannez le QR code avec votre application, puis saisissez le code affiché pour confirmer.');
    }

    public function enable(Request $request, TotpService $totp, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:10']], [
            'code.required' => 'Saisissez le code à 6 chiffres affiché par votre application.',
        ]);

        $secret = $totp->pendingSecret($request->session());
        if (! $secret) {
            return back()->withErrors(['code' => 'La configuration a expiré. Recommencez.']);
        }

        $step = $totp->match($secret, (string) $request->input('code'));
        if ($step === null) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez que l’heure de votre téléphone est automatique, puis réessayez.']);
        }

        $user = $request->user();
        $user->forceFill([
            'totp_secret' => $secret,
            'totp_enabled_at' => now(),
            'totp_last_step' => $step,
            'two_factor_enabled' => true,
        ])->save();
        $request->session()->forget('totp_pending');

        $redirect = back()->with('status', 'Application d’authentification activée.');
        if (empty($user->two_factor_recovery)) {
            $redirect->with('recovery_codes', $twoFactor->generateRecoveryCodes($user));
        }

        Log::info('Application d’authentification activée', ['user_id' => $user->id]);
        $this->audit('Application d’authentification activée', $request);

        return $redirect;
    }

    public function cancel(Request $request): RedirectResponse
    {
        $request->session()->forget('totp_pending');

        return back()->with('status', 'Configuration annulée.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $this->confirmPassword($request);
        $user = $request->user();

        $user->forceFill(['totp_secret' => null, 'totp_enabled_at' => null, 'totp_last_step' => null])->save();
        app(\App\Services\SessionService::class)->endOthers($user, $request->session()->getId());
        Log::info('Application d’authentification désactivée', ['user_id' => $user->id]);
        $this->audit('Application d’authentification désactivée', $request);

        return back()->with('status', 'Application d’authentification désactivée. Les codes arriveront par email.');
    }

    private function audit(string $action, Request $request): void
    {
        $user = $request->user();
        $to = config('jnuaf_audit.account_audit_email');

        if (! $to || ! $user->isAdmin()) {
            return;
        }

        try {
            Mail::to($to)->send(new AccountAudit($action, [
                'Compte' => (string) $user->name,
                'Email' => (string) $user->email,
            ], $user, $request->ip()));
        } catch (\Throwable $exception) {report($exception);
        }
    }
}
