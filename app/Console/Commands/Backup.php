<?php



namespace App\Console\Commands;



use Illuminate\Console\Command;



class Backup extends Command

{

    protected $signature = 'jnuaf:backup {--keep=14}';

    protected $description = 'Sauvegarde la base et les fichiers prives';



    public function handle(): int

    {

        $dir = '/home/jnuaf-sauvegardes';

        if (! is_dir($dir) && ! @mkdir($dir, 0700, true)) {

            $this->error("Impossible de creer $dir");

            return self::FAILURE;

        }

        $c = config('database.connections.mysql');

        $stamp = date('Ymd_His');



        $cnf = tempnam(sys_get_temp_dir(), 'my');

        chmod($cnf, 0600);

        $conf = "[client]\nuser=\"".addcslashes((string) $c['username'], '"\\')."\"\n"

            ."password=\"".addcslashes((string) $c['password'], '"\\')."\"\n"

            ."host=\"".$c['host']."\"\n";

        if (! empty($c['port'])) {

            $conf .= "port=\"".$c['port']."\"\n";

        }

        file_put_contents($cnf, $conf);



        $sql = "$dir/db_$stamp.sql.gz";

        $cmd = 'mysqldump --defaults-extra-file='.escapeshellarg($cnf)

            .' --single-transaction --quick --no-tablespaces '.escapeshellarg($c['database'])

            .' | gzip > '.escapeshellarg($sql);

        exec('bash -o pipefail -c '.escapeshellarg($cmd).' 2>&1', $out, $code);

        @unlink($cnf);

        if ($code !== 0 || ! is_file($sql) || filesize($sql) < 200) {

            $this->error('Echec de la sauvegarde de la base : '.implode(' ', $out));

            @unlink($sql);

            return self::FAILURE;

        }

        chmod($sql, 0600);



        $paths = ['home/jnuaf-documents-prives', 'htdocs/me/.env'];

        if (is_dir('/htdocs/me/storage/app')) {

            $paths[] = 'htdocs/me/storage/app';

        }

        $tar = "$dir/fichiers_$stamp.tar.gz";

        $cmd = 'tar -czf '.escapeshellarg($tar).' -C / '.implode(' ', array_map('escapeshellarg', $paths));

        exec($cmd.' 2>&1', $out2, $code2);

        if ($code2 > 1 || ! is_file($tar)) {

            $this->error('Echec de l\'archive des fichiers : '.implode(' ', $out2));

            return self::FAILURE;

        }

        chmod($tar, 0600);



        $keep = max(3, (int) $this->option('keep'));

        foreach (['db_*.sql.gz', 'fichiers_*.tar.gz'] as $pattern) {

            $files = glob("$dir/$pattern");

            rsort($files);

            foreach (array_slice($files, $keep) as $old) {

                @unlink($old);

            }

        }



        $this->info('Base : '.round(filesize($sql) / 1048576, 2).' Mo | Fichiers : '.round(filesize($tar) / 1048576, 2).' Mo');

        return self::SUCCESS;

    }

}

