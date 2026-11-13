<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use App\Models\SecurityEvent;

use Illuminate\Contracts\View\View;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;



class ConfirmPasswordController extends Controller

{

    public function show(): View

    {

        return view('auth.confirm-password');

    }



    public function store(Request $request): RedirectResponse

    {

        $request->validate(['password' => ['required', 'string']]);



        if (! Hash::check((string) $request->input('password'), (string) $request->user()->password)) {

            SecurityEvent::create([

                'user_id' => $request->user()->id,

                'action' => 'Confirmation du mot de passe refusée',

                'details' => ['page' => $request->session()->get('url.intended')],

                'ip' => $request->ip(),

                'user_agent' => substr((string) $request->userAgent(), 0, 500),

            ]);



            return back()->withErrors(['password' => 'Mot de passe incorrect.']);

        }



        $request->session()->put('auth.password_confirmed_at', time());



        return redirect()->intended(route('admin.users.index'));

    }

}

