<?php

namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use App\Rules\NotDerivedFromPassword;
use App\Rules\StrongPassword;
use Illuminate\Validation\Rules\Password;



class PasswordChangeController extends Controller

{

    public function edit(Request $request)

    {

        return view('admin.password-change', [

            'forced' => (bool) $request->user()->must_change_password,

        ]);

    }



    public function update(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'current_password' => ['required', 'current_password'],

            'password' => ['required', 'confirmed', 'different:current_password', new NotDerivedFromPassword((string) $request->input('current_password')), new StrongPassword($request->user())],

        ], [

            'current_password.required' => 'Saisissez votre mot de passe actuel.',

            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',

            'password.required' => 'Choisissez un nouveau mot de passe.',

            'password.confirmed' => 'La confirmation ne correspond pas.',

            'password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.',

        ]);



        $request->user()->forceFill([

            'password' => $data['password'],

            'must_change_password' => false,

        ])->save();



        $request->session()->regenerate();
        app(\App\Services\SessionService::class)->endOthers($request->user(), $request->session()->getId());



        if ($request->input('from') === 'profile') {
            return redirect()->route('admin.profile')->with('status', 'Mot de passe modifié.');
        }

        return redirect()->route('admin.dashboard')->with('status', 'Mot de passe modifié. Bienvenue !');

    }

}

