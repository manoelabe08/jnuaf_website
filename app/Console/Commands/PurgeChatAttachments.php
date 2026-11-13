<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

class PurgeChatAttachments extends Command

{

    protected $signature = 'jnuaf:purge-chat-attachments {--days=90} {--dry-run}';

    protected $description = 'Supprime les pieces jointes du chat anciennes ou liees a un message supprime';

    public function handle(): int

    {

        $days = max(7, (int) $this->option('days'));

        $dry = (bool) $this->option('dry-run');

        $root = rtrim((string) config('partner_portal.private_root'), '/') . '/chat';

        $realRoot = realpath($root);

        if ($realRoot === false) { $this->info('Aucun dossier de pieces jointes.'); return self::SUCCESS; }

        $limit = now()->subDays($days);

        $rows = DB::table('chat_messages')->whereNotNull('attachment_path')->where(function ($q) use ($limit) { $q->where('created_at', '<', $limit)->orWhereNotNull('deleted_at'); })->get(['id', 'body', 'attachment_path']);

        $files = 0;

        $messages = 0;

        foreach ($rows as $row) {

            $path = realpath($root . '/' . $row->attachment_path);

            $inside = $path !== false && str_starts_with($path, $realRoot . DIRECTORY_SEPARATOR) && is_file($path);

            if ($dry) {

                if ($inside) { $files++; }

            } else {

                if ($inside && @unlink($path)) { $files++; }

                DB::table('chat_messages')->where('id', $row->id)->update(['kind' => 'text', 'body' => trim(((string) $row->body) . ' [Pièce jointe supprimée]'), 'attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null, 'attachment_size' => null]);

            }

            $messages++;

        }

        $known = DB::table('chat_messages')->whereNotNull('attachment_path')->pluck('attachment_path')->flip();
        $orphans = 0;
        foreach (glob($realRoot . '/*/*') ?: [] as $file) {
            if (! is_file($file) || filemtime($file) > time() - 86400) { continue; }
            if (isset($known[substr($file, strlen($realRoot) + 1)])) { continue; }
            if (! $dry) { @unlink($file); }
            $orphans++;
        }
        $this->info(sprintf('%s%d fichier(s) orphelin(s).', $dry ? '[simulation] ' : '', $orphans));
        $this->info(sprintf('%s%d message(s) concerne(s), %d fichier(s) %s.', $dry ? '[simulation] ' : '', $messages, $files, $dry ? 'a supprimer' : 'supprime(s)'));

        return self::SUCCESS;

    }

}

