<?php



namespace App\Console\Commands;



use Illuminate\Console\Command;

use Illuminate\Support\Facades\Schema;



class BackupTest extends Command

{

    protected $signature = 'jnuaf:backup-test';

    protected $description = 'Verifie la derniere sauvegarde sans toucher a la production';



    public function handle(): int

    {

        $dir = '/home/jnuaf-sauvegardes';

        $dbs = glob("$dir/db_*.sql.gz");

        $tars = glob("$dir/fichiers_*.tar.gz");

        if (! $dbs || ! $tars) {

            $this->error('Aucune sauvegarde trouvee. Lance jnuaf:backup.');

            return self::FAILURE;

        }

        rsort($dbs);

        rsort($tars);

        [$db, $tar] = [$dbs[0], $tars[0]];

        $ok = true;

        $check = function (string $label, bool $res) use (&$ok) {

            $this->line(($res ? 'OK      ' : 'ECHEC   ').$label);

            $ok = $ok && $res;

        };



        exec('gzip -t '.escapeshellarg($db).' 2>&1', $o, $c);

        $check('Archive de la base lisible (gzip)', $c === 0);



        exec('gzip -dc '.escapeshellarg($db).' | tail -n 3', $tail);

        $check('Dump termine correctement', str_contains(implode("\n", $tail), 'Dump completed'));



        exec('gzip -dc '.escapeshellarg($db).' | grep -c "^CREATE TABLE"', $n);

        $inDump = (int) ($n[0] ?? 0);

        $inDb = count(\Illuminate\Support\Facades\DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'"));

        $check("Tables : $inDump dans le dump, $inDb dans la base", $inDump === $inDb && $inDb > 0);



        exec('tar -tzf '.escapeshellarg($tar).' 2>&1', $list, $c2);

        $check('Archive des fichiers lisible ('.count($list).' elements)', $c2 === 0 && count($list) > 0);

        $check('.env present dans l\'archive', (bool) preg_grep('#htdocs/me/\.env$#', $list));

        $check('Dossier prive present dans l\'archive', (bool) preg_grep('#^home/jnuaf-documents-prives/#', $list));



        $age = (time() - filemtime($db)) / 3600;

        $check('Sauvegarde recente ('.round($age, 1).' h)', $age < 48);



        $this->line(PHP_EOL.($ok ? 'RESULTAT : sauvegarde valide' : 'RESULTAT : probleme a corriger'));

        return $ok ? self::SUCCESS : self::FAILURE;

    }

}

