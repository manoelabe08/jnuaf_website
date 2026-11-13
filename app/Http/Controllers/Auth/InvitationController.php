<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

use App\Models\User;

use App\Rules\StrongPassword;

use Illuminate\Auth\Events\PasswordReset;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Password;

use Illuminate\Support\Str;

use Illuminate\View\View;

class InvitationController extends Controller

{

    public function show(Request $request, string $token): View

    {

        return view('admin.auth.invitation', ['token' => $token, 'email' => $request->query('email')]);

    }

    public function store(Request $request): RedirectResponse

    {

        $request->validate([

            'token' => ['required'],

            'email' => ['required', 'email'],

            'password' => ['required', 'confirmed', new StrongPassword(null, (string) $request->input('email'))],

        ]);

        $status = Password::broker('invitations')->reset(

            $request->only('email', 'password', 'password_confirmation', 'token'),

            function (User $user, string $password) {

                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60), 'must_change_password' => false])->save();

                app(\App\Services\SessionService::class)->endAll($user);

                event(new PasswordReset($user));

            }

        );

        return $status === Password::PASSWORD_RESET

            ? redirect()->route('login')->with('status', "Mot de passe d\u{e9}fini. Vous pouvez vous connecter.")

            : back()->withInput($request->only('email'))->withErrors(['email' => "Ce lien est invalide ou a expir\u{e9}. Demandez une nouvelle invitation \u{e0} l\u{2019}\u{e9}quipe."]);

    }

}

