<?php



namespace App\Services;



use App\Models\User;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Str;



class AccountAnonymizer

{

    private const OPTIONAL_COLUMNS = [

        'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at',

        'totp_secret', 'totp_confirmed_at', 'photo', 'avatar', 'photo_path', 'avatar_path',

        'phone', 'telephone', 'pending_email',

    ];



    public function anonymize(User $user): void

    {
        if ($user->isSuperAdmin() || (int) $user->getKey() === 1) {
            throw new \RuntimeException('Compte protege : anonymisation interdite.');
        }

        $id = $user->getKey();

        $oldEmail = (string) $user->email;

        app(SessionService::class)->endAll($user);

        $user->forceFill([

            'name' => 'Compte supprime #'.$id,

            'email' => 'supprime-'.$id.'-'.Str::lower(Str::random(8)).'@anonyme.invalid',

            'password' => Hash::make(Str::random(64)),

            'remember_token' => Str::random(60),

            'must_change_password' => true,

            'role' => 'coordinator',

            'suspended_at' => now(),

            'anonymized_at' => now(),

        ])->save();

        foreach (self::OPTIONAL_COLUMNS as $col) {

            if (Schema::hasColumn('users', $col)) {

                try {

                    DB::table('users')->where('id', $id)->update([$col => null]);

                } catch (\Throwable $e) {

                    report($e);

                }

            }

        }

        \App\Models\KnownDevice::where('user_id', $id)->delete();

        if (Schema::hasTable('two_factor_challenges')) {

            DB::table('two_factor_challenges')->where('user_id', $id)->delete();

        }

        foreach (['users', 'invitations'] as $broker) {

            $t = config("auth.passwords.$broker.table");

            if ($t && Schema::hasTable($t)) {

                DB::table($t)->where('email', $oldEmail)->delete();

            }

        }

    }

}

