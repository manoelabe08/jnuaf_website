<?php



namespace App\Console\Commands;



use App\Services\TrashService;

use Illuminate\Console\Command;



class TrashPurge extends Command

{

    protected $signature = 'trash:purge {--now : Vide toute la corbeille immediatement}';



    protected $description = 'Supprime definitivement les elements de la corbeille de plus de 45 jours';



    public function handle(): int

    {

        $n = TrashService::purge((bool) $this->option('now'));

        $this->info("Elements purges : $n");



        return self::SUCCESS;

    }

}

