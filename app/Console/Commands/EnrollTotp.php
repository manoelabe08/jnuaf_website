<?php



namespace App\Console\Commands;



use App\Models\User;

use App\Services\TotpService;

use App\Services\TwoFactorService;

use Illuminate\Console\Command;



class EnrollTotp extends Command

{

    protected $signature = 'user:enroll-totp {email}';

    protected $description = 'Configure l’application d’authentification d’un compte depuis le terminal (clé à saisir à la main)';



    public function handle(TotpService $totp, TwoFactorService $twoFactor): int

    {

        $user = User::where('email', mb_strtolower((string) $this->argument('email')))->first();

        if (! $user) {

            $this->error('Compte introuvable.');



            return self::FAILURE;

        }



        if ($user->hasTotp() && ! $this->confirm('Une application est déjà configurée pour ce compte. La remplacer ?')) {

            return self::SUCCESS;

        }



        $secret = $totp->generateSecret();



        $this->newLine();

        $this->info('Dans Google Authenticator ou Microsoft Authenticator :');

        $this->line('  Ajouter un compte > Saisir une clé de configuration (ou "Entrer une clé fournie")');

        $this->line('  Nom du compte : '.$user->email);

        $this->line('  Clé           : '.implode(' ', str_split($secret, 4)));

        $this->line('  Type de clé   : basée sur l’heure');

        $this->newLine();



        $code = (string) $this->ask('Saisissez le code à 6 chiffres affiché par l’application');

        $step = $totp->match($secret, $code);



        if ($step === null) {

            $this->error('Code incorrect : rien n’a été enregistré. Vérifiez que l’heure du téléphone est automatique et recommencez.');



            return self::FAILURE;

        }



        $user->forceFill([

            'totp_secret' => $secret,

            'totp_enabled_at' => now(),

            'totp_last_step' => $step,

            'two_factor_enabled' => true,

        ])->save();



        $this->info('Application d’authentification activée pour '.$user->email.'.');



        if (empty($user->two_factor_recovery)) {

            $this->newLine();

            $this->warn('Codes de secours (notez-les maintenant, ils ne seront plus affichés) :');

            foreach ($twoFactor->generateRecoveryCodes($user) as $recovery) {

                $this->line('  '.$recovery);

            }

        }



        $this->newLine();

        $this->line('Pensez à effacer l’écran du terminal : clear');



        return self::SUCCESS;

    }

}

