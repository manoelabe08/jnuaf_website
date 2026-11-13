<?php



namespace App\Console\Commands;



use App\Models\User;

use App\Services\AccountAnonymizer;

use Illuminate\Console\Command;



class PurgeDeletedAccounts extends Command

{

    protected $signature = 'accounts:purge-deleted {--dry-run} {--id=}';



    protected $description = 'Anonymise les comptes supprimes dont le delai de 30 jours est ecoule.';



    public function handle(): int

    {

        $dry = (bool) $this->option('dry-run');

        $id = $this->option('id');

        $query = User::query()->whereNotNull('anonymize_after')->whereNull('anonymized_at');

        if ($id) {

            $query->where('id', (int) $id);

        } else {

            $query->where('anonymize_after', '<=', now());

        }

        $count = 0;

        foreach ($query->get() as $user) {

            if ($user->isSuperAdmin() || (int) $user->getKey() === 1) {

                $this->warn('Ignore (protege) : #'.$user->getKey());

                continue;

            }

            $this->line(($dry ? 'Anonymiserait : ' : 'Anonymise : ').'#'.$user->getKey());

            if (! $dry) {

                app(AccountAnonymizer::class)->anonymize($user);

            }

            $count++;

        }

        $this->info('Comptes : '.$count);



        return self::SUCCESS;

    }

}

