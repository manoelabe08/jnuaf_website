<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::lower($credentials['email']).'|'.$request->ip();

        $blocked = app(\App\Services\LoginThrottle::class)->tooMany($credentials['email'], (string) $request->ip());
        if ($blocked > 0) {
            throw ValidationException::withMessages(['email' => 'Trop de tentatives. Réessayez dans '.ceil($blocked / 60).' minute(s).']);
        }

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            app(\App\Services\LoginThrottle::class)->fail($credentials['email'], (string) $request->ip());
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        
RateLimiter::clear($key);
        app(\App\Services\LoginThrottle::class)->clear($credentials['email']);



        $candidate = Auth::user();

        if ($candidate && $candidate->must_change_password && $candidate->password_expires_at && $candidate->password_expires_at->isPast()) {

            Auth::logout();

            throw ValidationException::withMessages([

                'email' => 'Votre mot de passe temporaire a expiré (validité : '.\App\Models\User::TEMP_PASSWORD_DAYS.' jours). Utilisez « Mot de passe oublié » pour en définir un nouveau.',

            ]);

        }

        
$gateUser = Auth::user();

        $twoFactor = app(\App\Services\TwoFactorService::class);

        if ($twoFactor->required($gateUser)) {

            $remember = $request->boolean('remember') && ! $gateUser->isAdmin();

            Auth::logout();

            $request->session()->regenerate();

            $request->session()->put('two_factor', ['user_id' => $gateUser->id, 'remember' => $remember, 'started' => now()->timestamp]);

            $useTotp = $gateUser->hasTotp();
            $sent = $twoFactor->sendLoginCode($gateUser, $request->ip(), ! $useTotp);



            return redirect()->route('two-factor.challenge')->with('status', $sent

                ? ($useTotp ? 'Saisissez le code affiché par votre application d’authentification.' : 'Un code de vérification vient d’être envoyé à votre adresse email.')

                : 'L’envoi du code a échoué. Utilisez « Renvoyer le code » ou un code de secours.');

        }



        $request->session()->regenerate();

        $request->user()->forceFill(['last_login_at' => now()])->save();
        app(\App\Services\DeviceAlertService::class)->check($request->user(), $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
