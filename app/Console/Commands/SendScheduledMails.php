<?php



namespace App\Console\Commands;



use App\Mail\TeamMessage;

use App\Models\ScheduledMail;

use App\Services\AdminMailboxService;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;



class SendScheduledMails extends Command

{

    protected $signature = 'mailbox:send-scheduled';

    protected $description = 'Envoie les messages de la messagerie dont l’heure de programmation est arrivée';



    public function handle(AdminMailboxService $mailbox): int

    {

        ScheduledMail::where('status', 'sending')->where('updated_at', '<', now()->subMinutes(15))

            ->update(['status' => 'uncertain', 'error' => 'Traitement interrompu : vérifier les messages envoyés avant de renvoyer.']);



        $due = ScheduledMail::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(3)
            ->get();

        $done = 0;
        foreach ($due as $mail) {
            $claimed = ScheduledMail::whereKey($mail->id)->where('status', 'scheduled')->update(['status' => 'sending']);
            if ($claimed !== 1) {
                continue;
            }
            $this->deliver($mail->fresh(), $mailbox);
            $done++;
        }

        $this->info($done.' message(s) programmé(s) traité(s).');

        return self::SUCCESS;
    }

    private function deliver(ScheduledMail $mail, AdminMailboxService $mailbox): void
    {
        $dir = rtrim((string) config('partner_portal.private_root'), '/').'/scheduled-mails/'.$mail->id;

        $files = [];
        foreach ((array) $mail->attachments as $a) {
            $path = $dir.'/'.($a['stored'] ?? '');
            if (! is_file($path)) {
                $this->markFailed($mail, 'Pièce jointe introuvable : message non envoyé.');
                return;
            }
            $files[] = ['path' => $path, 'name' => $a['name'], 'mime' => $a['mime']];
        }

        $r = (array) $mail->recipients;
        $to = $r['to'] ?? [];
        $cc = $r['cc'] ?? [];
        $bcc = $r['bcc'] ?? [];
        $all = array_merge($to, $cc, $bcc);

        $batches = $mail->individual
            ? array_map(fn (string $e) => ['to' => [$e], 'cc' => [], 'bcc' => []], $all)
            : [['to' => $to, 'cc' => $cc, 'bcc' => $bcc]];

        $delivered = 0;
        $failed = [];
        $copies = [];

        foreach ($batches as $batch) {
            try {
                $pending = Mail::mailer('team')->to($batch['to']);
                if ($batch['cc'] !== []) {
                    $pending->cc($batch['cc']);
                }
                if ($batch['bcc'] !== []) {
                    $pending->bcc($batch['bcc']);
                }

                $sent = $pending->send(new TeamMessage($mail->subject, $mail->body, $files));
                $delivered += count($batch['to']) + count($batch['cc']) + count($batch['bcc']);

                if ($sent !== null) {
                    $copies[] = $sent->toString();
                }
            } catch (\Throwable $exception) {
                report($exception);
                $failed = array_merge($failed, $batch['to'], $batch['cc'], $batch['bcc']);
            }
        }

        if ($delivered === 0) {
            $this->markFailed($mail, 'L’envoi a échoué : aucun message n’est parti.');
            $this->cleanup($mail, $dir);
            return;
        }

        $note = null;
        try {
            $mailbox->appendManyToSent($copies);
            $mailbox->disconnect();
        } catch (\Throwable $exception) {
            report($exception);
            $note = 'La copie dans « Envoyés » n’a pas pu être enregistrée. ';
        }
        if ($failed !== []) {
            $note .= 'Échec pour : '.implode(', ', array_slice($failed, 0, 5)).'.';
        }

        $mail->update(['status' => 'sent', 'sent_at' => now(), 'error' => $note ? trim($note) : null]);
        $this->cleanup($mail, $dir);

        Log::info('Message programmé envoyé', [
            'scheduled_mail_id' => $mail->id,
            'recipients' => count($all),
            'failed' => count($failed),
        ]);
    }

    private function markFailed(ScheduledMail $mail, string $message): void
    {
        $mail->update(['status' => 'failed', 'error' => $message]);
    }

    private function cleanup(ScheduledMail $mail, string $dir): void
    {
        foreach ((array) $mail->attachments as $a) {
            @unlink($dir.'/'.($a['stored'] ?? ''));
        }
        @rmdir($dir);
    }
}
