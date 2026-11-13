<?php



namespace App\Console\Commands;



use App\Services\TrashService;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;



class TrashTree extends Command

{

    protected $signature = 'trash:tree {table} {id}';



    protected $description = 'Montre les donnees liees qui seraient emportees dans la corbeille (sans rien supprimer)';



    public function handle(): int

    {

        $row = DB::table($this->argument('table'))->where('id', (int) $this->argument('id'))->first();

        if (! $row) {

            $this->error('Introuvable.');



            return self::FAILURE;

        }

        $out = [];

        foreach (TrashService::tree($this->argument('table'), (array) $row) as $t => $rows) {

            $out[] = [$t, count($rows)];

        }

        $this->table(['Table liee', 'Lignes'], $out);



        return self::SUCCESS;

    }

}

