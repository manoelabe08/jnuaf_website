<?php



namespace App\Services;



use App\Mail\AccountAudit;

use App\Mail\TwoFactorCode;

use App\Models\User;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;



class TwoFactorService

{

    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;



    public function required(?User $user): bool

    {

        if (! $user) {

            return false;

        }



        return (bool) $user->two_factor_enabled

            || ($user->isAdmin() && config('jnuaf_security.two_factor_admin_required', true));

    }



    public function sendLoginCode(User $user, ?string $ip, bool $sendMail = true): bool

    {

        DB::table('two_factor_challenges')

            ->where('user_id', $user->id)

            ->orWhere('expires_at', '<', now())

            ->delete();



        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);



        DB::table('two_factor_challenges')->insert([

            'user_id' => $user->id,

            'code_hash' => $this->hash($code),

            'attempts' => 0,

            'expires_at' => now()->addMinutes(self::TTL_MINUTES),

            'created_at' => now(),

        ]);



        try {

            if ($sendMail) {
                Mail::to($user->email)->send(new TwoFactorCode($user, $code, self::TTL_MINUTES, $ip));
            }



            return true;

        } catch (\Throwable $exception) {

            report($exception);



            return false;

        }

    }



    /** @return string ok|invalid|locked|expired */

    public function verify(User $user, string $input, ?string $ip = null): string

    {

        $input = trim($input);

        $row = DB::table('two_factor_challenges')->where('user_id', $user->id)->first();



        if (! $row || Carbon::parse($row->expires_at)->isPast()) {

            $this->clear($user);



            return 'expired';

        }



        if ($row->attempts >= self::MAX_ATTEMPTS) {

            $this->clear($user);



            return 'locked';

        }



        DB::table('two_factor_challenges')->where('id', $row->id)->increment('attempts');



        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {

            $digits = str_replace(' ', '', $input);
            if ($user->hasTotp() && app(\App\Services\TotpService::class)->verify($user, $digits)) {
                $this->clear($user);

                return 'ok';
            }

            if (hash_equals($row->code_hash, $this->hash($digits))) {

                $this->clear($user);



                return 'ok';

            }

        } elseif ($this->useRecoveryCode($user, $input)) {

            $this->clear($user);



            return 'ok';

        }



        if ($row->attempts + 1 >= self::MAX_ATTEMPTS) {

            $this->clear($user);

            $this->alertLockout($user, $ip);
            app(\App\Services\LoginThrottle::class)->twoFactorFailure($user);



            return 'locked';

        }



        return 'invalid';

    }



    public function clear(User $user): void

    {

        DB::table('two_factor_challenges')->where('user_id', $user->id)->delete();

    }



    public function generateRecoveryCodes(User $user): array

    {

        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';

        $display = [];

        $hashes = [];



        for ($i = 0; $i < 8; $i++) {

            $raw = '';

            for ($j = 0; $j < 8; $j++) {

                $raw .= $alphabet[random_int(0, strlen($alphabet) - 1)];

            }

            $hashes[] = $this->hash($raw);

            $display[] = strtoupper(substr($raw, 0, 4).'-'.substr($raw, 4));

        }



        $user->forceFill(['two_factor_recovery' => $hashes])->save();



        return $display;

    }



    private function useRecoveryCode(User $user, string $input): bool

    {

        $normalized = Str::lower(preg_replace('/[^a-z0-9]/i', '', $input));

        if (strlen($normalized) !== 8) {

            return false;

        }



        $hashes = (array) $user->two_factor_recovery;

        $candidate = $this->hash($normalized);



        foreach ($hashes as $index => $stored) {

            if (hash_equals((string) $stored, $candidate)) {

                unset($hashes[$index]);

                $user->forceFill(['two_factor_recovery' => array_values($hashes)])->save();



                return true;

            }

        }



        return false;

    }



    private function alertLockout(User $user, ?string $ip): void

    {

        if (! $user->isAdmin()) {

            return;

        }



        $to = config('jnuaf_audit.account_audit_email');

        if (! $to) {

            return;

        }



        try {

            Mail::to($to)->send(new AccountAudit('Échecs répétés du code de connexion (compte admin)', [

                'Compte' => (string) $user->name,

                'Email' => (string) $user->email,

                'Essais échoués' => (string) self::MAX_ATTEMPTS,

            ], null, $ip));

        } catch (\Throwable $exception) {

            report($exception);

        }

    }



    private function hash(string $value): string

    {

        return hash_hmac('sha256', $value, (string) config('app.key'));

    }

}

