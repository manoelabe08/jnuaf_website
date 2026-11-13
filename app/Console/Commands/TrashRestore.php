<?php



namespace App\Console\Commands;



use App\Services\TrashService;

use Illuminate\Console\Command;



class TrashRestore extends Command

{

    protected $signature = 'trash:restore {id}';



    protected $description = 'Restaure un element de la corbeille';



    public function handle(): int

    {

        $this->info(TrashService::restore((int) $this->argument('id')));



        return self::SUCCESS;

    }

}

