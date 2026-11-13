<?php



namespace App\Console\Commands;



use App\Models\CommitteePoleProposal;

use Illuminate\Console\Command;



class ExpirePoleProposals extends Command

{

    protected $signature = 'committee:expire-pole-proposals';



    protected $description = 'Marque comme expirees les propositions de pole sans reponse.';



    public function handle(): int

    {

        $n = CommitteePoleProposal::where('status', 'pending')->where('expires_at', '<', now())->update(['status' => 'expired']);

        $this->info('Expirees : '.$n);



        return self::SUCCESS;

    }

}

