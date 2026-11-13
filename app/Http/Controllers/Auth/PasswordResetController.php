<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('admin.auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        // Même réponse que le compte existe ou non : on ne révèle pas quels emails ont un compte.
        return back()->with('status', 'Si un compte existe pour cette adresse, un lien de réinitialisation vient d\'être envoyé (valable 3 minutes).');
    }

    public function edit(Request $request, string $token): View
    {
        return view('admin.auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', new \App\Rules\StrongPassword(null, (string) $request->input('email'))],
        ]);

        
$existing = User::where('email', $request->input('email'))->first();

        if ($existing && \Illuminate\Support\Facades\Hash::check((string) $request->input('password'), $existing->password)) {

            return back()->withInput($request->only('email'))->withErrors(['password' => 'Le nouveau mot de passe doit être différent de l’actuel.']);

        }



        $status = Password::reset(

            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'must_change_password' => false])->save();
                app(\App\Services\SessionService::class)->endAll($user);
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Mot de passe modifié. Vous pouvez vous connecter.')
            : back()->withInput($request->only('email'))->withErrors(['email' => 'Ce lien est invalide ou a expiré. Demandez-en un nouveau.']);
    }
}
