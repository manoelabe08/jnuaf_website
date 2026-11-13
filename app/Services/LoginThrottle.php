<?php



namespace App\Services;



use App\Mail\AccountAudit;

use App\Models\User;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\RateLimiter;

use Illuminate\Support\Str;



class LoginThrottle

{

    private const IP_MAX = 40;

    private const ACCOUNT_MAX = 15;



    /** Secondes restantes avant de pouvoir reessayer, 0 si autorise. */

    public function tooMany(string $email, string $ip): int

    {

        foreach ([['login-ip:'.$ip, self::IP_MAX], [$this->accountKey($email), self::ACCOUNT_MAX]] as [$key, $max]) {

            if (RateLimiter::tooManyAttempts($key, $max)) {

                return max(1, RateLimiter::availableIn($key));

            }

        }



        return 0;

    }



    public function fail(string $email, string $ip): void

    {

        RateLimiter::hit('login-ip:'.$ip, 600);



        $key = $this->accountKey($email);

        RateLimiter::hit($key, 900);



        if (RateLimiter::attempts($key) === self::ACCOUNT_MAX) {

            $this->alert($email, $ip);

        }

    }



    public function clear(string $email): void

    {

        RateLimiter::clear($this->accountKey($email));

    }



    public function twoFactorFailure(User $user): void

    {

        $key = 'tf-lock:'.$user->id;

        RateLimiter::hit($key, 3600);



        if (RateLimiter::attempts($key) >= 3) {

            $account = $this->accountKey($user->email);

            for ($i = 0; $i < self::ACCOUNT_MAX; $i++) {

                RateLimiter::hit($account, 900);

            }

        }

    }



    private function accountKey(string $email): string

    {

        return 'login-acct:'.sha1(Str::lower(trim($email)));

    }



    private function alert(string $email, string $ip): void

    {

        $user = User::where('email', Str::lower(trim($email)))->first();

        $to = config('jnuaf_audit.account_audit_email');



        if (! $user || ! $user->isAdmin() || ! $to) {

            return;

        }



        try {

            Mail::to($to)->send(new AccountAudit('Trop de tentatives de connexion (compte admin)', [

                'Compte' => (string) $user->name,

                'Email' => (string) $user->email,

                'Échecs' => (string) self::ACCOUNT_MAX,

                'Blocage' => '15 minutes',

            ], null, $ip));

        } catch (\Throwable $exception) {

            report($exception);

        }

    }

}

