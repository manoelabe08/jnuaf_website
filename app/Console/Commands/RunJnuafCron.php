<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class RunJnuafCron extends Command
{
    protected $signature = 'jnuaf:cron';
    protected $description = 'Traite les accès partenaires, la galerie puis les newsletters';
    public function handle(): int
    {
        $lock = fopen(storage_path('framework/jnuaf-cron.lock'), 'c');
        if ($lock === false) { $this->error('Impossible de créer le verrou du traitement.'); return self::FAILURE; }
        if (! flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); $this->info('Un traitement est déjà en cours.'); return self::SUCCESS; }
        $result = self::SUCCESS;
        try {
            $commands = ['gallery:sync-folders', 'newsletter:send-batch', 'accounts:remind-expiring-passwords', 'mailbox:send-scheduled', 'mailbox:notify-new', 'schedule:run'];
            if (\Illuminate\Support\Facades\Cache::add('cards:purge:last-run', now()->toDateTimeString(), now()->addDay())) {
                $commands[] = 'cards:purge';
            }
            foreach ($commands as $command) {
                $this->line('['.now()->format('Y-m-d H:i:s').'] '.$command);
                try {
                    $exitCode = $this->call($command);
                    if ($exitCode !== 0) { $result = self::FAILURE; $this->error($command.' a retourné le code '.$exitCode); }
                } catch (\Throwable $exception) {
                    report($exception); $this->error('Échec de '.$command.' : consultez le journal Laravel.'); $result = self::FAILURE;
                }
            }
        } finally { flock($lock, LOCK_UN); fclose($lock); }
        return $result;
    }
}
