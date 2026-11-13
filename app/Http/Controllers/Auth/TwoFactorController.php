<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use App\Models\User;

use App\Services\TwoFactorService;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\RateLimiter;



class TwoFactorController extends Controller

{

    private function state(Request $request): ?array

    {

        $state = $request->session()->get('two_factor');



        if (! is_array($state) || empty($state['user_id']) || ($state['started'] ?? 0) < now()->subMinutes(15)->timestamp) {

            return null;

        }



        return $state;

    }



    private function pendingUser(Request $request): ?User

    {

        $state = $this->state($request);



        return $state ? User::find($state['user_id']) : null;

    }



    public function show(Request $request)

    {

        $user = $this->pendingUser($request);

        if (! $user) {

            return redirect()->route('login');

        }



        [$local, $domain] = array_pad(explode('@', $user->email, 2), 2, '');



        return view('admin.auth.two-factor', [

            'maskedEmail' => mb_substr($local, 0, 2).'***@'.$domain,
            'totp' => $user->hasTotp(),
            'emailFallback' => ! $user->hasTotp() || ($user->isAdmin() ? (bool) config('jnuaf_security.admin_email_fallback', false) : (bool) config('jnuaf_security.totp_email_fallback', true)),      ]);
    }

    public function verify(Request $request, TwoFactorService $service)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Votre session de connexion a expiré. Recommencez.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:20']], [
            'code.required' => 'Saisissez le code reçu par email ou un code de secours.',
        ]);

        $result = $service->verify($user, (string) $request->input('code'), $request->ip());

        if ($result === 'ok') {
            $remember = (bool) ($this->state($request)['remember'] ?? false);
            $request->session()->forget('two_factor');

            Auth::loginUsingId($user->id, $remember);
            $request->session()->regenerate();
            $user->forceFill(['last_login_at' => now()])->save();
            app(\App\Services\DeviceAlertService::class)->check($user, $request);

            return redirect()->intended(route('admin.dashboard'));
        }

        if ($result === 'locked' || $result === 'expired') {
            $request->session()->forget('two_factor');

            return redirect()->route('login')->withErrors([
                'email' => $result === 'locked'
                    ? 'Trop d’essais incorrects. Reconnectez-vous pour recevoir un nouveau code.'
                    : 'Le code a expiré. Reconnectez-vous pour en recevoir un nouveau.',
            ]);
        }

        return back()->withErrors(['code' => 'Code incorrect.']);
    }

    public function resend(Request $request, TwoFactorService $service)
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->hasTotp() && ($user->isAdmin() ? ! config('jnuaf_security.admin_email_fallback', false) : ! config('jnuaf_security.totp_email_fallback', true))) {
            return back()->withErrors(['code' => 'L’envoi par email est désactivé pour votre compte : utilisez votre application ou un code de secours.']);
        }

        $key = 'tf-resend:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['code' => 'Trop de demandes. Réessayez plus tard.']);
        }

        $last = DB::table('two_factor_challenges')->where('user_id', $user->id)->value('created_at');
        if ($last && Carbon::parse($last)->gt(now()->subSeconds(60))) {
            return back()->with('status', 'Un code vient d’être envoyé. Patientez une minute avant d’en demander un autre.');
        }

        RateLimiter::hit($key, 3600);
        $sent = $service->sendLoginCode($user, $request->ip());

        return back()->with('status', $sent
            ? 'Un nouveau code vient d’être envoyé.'
            : 'L’envoi du code a échoué. Réessayez ou utilisez un code de secours.');
    }

    public function cancel(Request $request)
    {
        $user = $this->pendingUser($request);
        if ($user) {
            app(TwoFactorService::class)->clear($user);
        }
        $request->session()->forget('two_factor');

        return redirect()->route('login');
    }
}
