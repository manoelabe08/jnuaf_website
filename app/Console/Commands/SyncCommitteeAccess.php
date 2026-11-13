<?php



namespace App\Console\Commands;



use App\Models\User;

use App\Services\CommitteeAccessService;

use Illuminate\Console\Command;



class SyncCommitteeAccess extends Command

{

    protected $signature = 'access:sync-committee {--dry-run : Affiche sans suspendre}';



    protected $description = 'Suspend les comptes de comite dont la candidature n est plus valable';



    public function handle(CommitteeAccessService $service): int

    {

        if ($this->option('dry-run')) {

            $n = User::where('access_source', 'committee')->whereNull('suspended_at')->get()

                ->reject(fn (User $u) => $u->committeeAccessValid())->count();

            $this->info($n.' compte(s) seraient suspendus.');



            return self::SUCCESS;

        }



        $this->info($service->sync().' compte(s) suspendu(s).');



        return self::SUCCESS;

    }

}

