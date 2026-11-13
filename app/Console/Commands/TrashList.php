<?php



namespace App\Console\Commands;



use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;



class TrashList extends Command

{

    protected $signature = 'trash:list';



    protected $description = 'Liste la corbeille (elements restaurables)';



    public function handle(): int

    {

        $rows = DB::table('trash_items')->whereNull('restored_at')->orderByDesc('id')->limit(100)->get(['id', 'model', 'original_id', 'label', 'deleted_at', 'purge_at']);

        $this->table(['ID corbeille', 'Type', 'No origine', 'Libelle', 'Supprime le', 'Purge le'], $rows->map(fn ($r) => (array) $r)->all());



        return self::SUCCESS;

    }

}

