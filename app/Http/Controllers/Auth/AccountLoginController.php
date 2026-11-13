<?php



namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;

use App\Mail\AccountLoginLinkMail;

use App\Models\User;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;

use Illuminate\View\View;



class AccountLoginController extends Controller

{

    private const MINUTES = 15;



    public function show(): View|RedirectResponse

    {

        if (Auth::check()) {

            return redirect()->route('admin.dashboard');

        }



        return view('account.request', ['sent' => (bool) session('sent'), 'expired' => (bool) session('expired')]);

    }



    public function sendLink(Request $request): RedirectResponse

    {

        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);

        $email = mb_strtolower(trim($data['email']));



        $user = User::whereRaw('lower(email) = ?', [$email])

            ->whereNull('anonymized_at')->whereNull('suspended_at')->first();



        if ($user) {

            $recent = DB::table('account_login_links')

                ->where('user_id', $user->id)->where('created_at', '>=', now()->subHour())->count();

            if ($recent < 3) {

                $raw = Str::random(48);

                DB::table('account_login_links')->insert([

                    'user_id' => $user->id,

                    'token_hash' => hash('sha256', $raw),

                    'expires_at' => now()->addMinutes(self::MINUTES),

                    'ip' => $request->ip(),

                    'created_at' => now(),

                ]);

                try {

                    Mail::to($user->email)->send(new AccountLoginLinkMail($user, route('account.password', $raw), self::MINUTES));

                } catch (\Throwable $e) {

                    report($e);

                }

            }

        }



        return redirect()->route('account.show')->with('sent', true);

    }public function passwordForm(string $token): View|RedirectResponse

    {

        if (Auth::check()) {

            return redirect()->route('admin.dashboard');

        }

        [$link, $user] = $this->resolve($token);

        if (! $user) {

            return redirect()->route('account.show')->with('expired', true);

        }



        return view('account.password', ['token' => $token, 'maskedEmail' => $this->mask((string) $user->email)]);

    }



    public function login(Request $request, string $token): RedirectResponse

    {

        [$link, $user] = $this->resolve($token);

        if (! $user) {

            return redirect()->route('account.show')->with('expired', true);

        }



        $request->validate(['password' => ['required', 'string']]);

        $request->merge(['email' => $user->email]);



        $response = app(LoginController::class)->login($request);



        DB::table('account_login_links')->where('id', $link->id)->update(['used_at' => now()]);



        return $response;

    }



    private function resolve(string $token): array

    {

        $link = DB::table('account_login_links')->where('token_hash', hash('sha256', $token))->first();

        if (! $link || $link->used_at || ! $link->expires_at || Carbon::parse($link->expires_at)->isPast()) {

            return [null, null];

        }

        $user = User::find($link->user_id);

        if (! $user || $user->suspended_at || $user->anonymized_at) {

            return [null, null];

        }



        return [$link, $user];

    }



    private function mask(string $email): string

    {

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');



        return mb_substr($local, 0, 2).str_repeat('*', max(3, mb_strlen($local) - 2)).'@'.$domain;

    }

}

