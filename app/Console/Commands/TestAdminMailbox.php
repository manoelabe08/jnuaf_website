<?php

namespace App\Console\Commands;

use App\Services\AdminMailboxService;
use Illuminate\Console\Command;

class TestAdminMailbox extends Command
{
    protected $signature = 'mailbox:test';

    protected $description = 'Teste la connexion IMAP en lecture seule à la boîte team@jnuaf.org';

    public function handle(AdminMailboxService $mailbox): int
    {
        try {
            $this->info('Connexion à la boîte administrative...');

            $folders = $mailbox->folders();

            $this->info('Connexion IMAP réussie.');
            $this->line('');
            $this->info('Dossiers disponibles :');

            foreach ($folders as $folder) {
                $this->line('- '.$folder);
            }

            $this->line('');
            $this->info('5 derniers messages de INBOX :');

            $messages = $mailbox->inboxPreview(5);

            if ($messages === []) {
                $this->line('Aucun message trouvé.');
            }

            foreach ($messages as $message) {
                $this->line('');
                $this->line('UID : '.$message['uid']);
                $this->line('De : '.$message['from_name'].' <'.$message['from'].'>');
                $this->line('Objet : '.$message['subject']);
                $this->line('Date : '.$message['date']);
                $this->line('Pièces jointes : '.$message['attachments']);
            }

            $mailbox->disconnect();

            $this->line('');
            $this->info('Test terminé. Aucun message n’a été modifié.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            report($exception);

            $this->error('Échec du test : '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}