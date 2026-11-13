<?php

namespace App\Console\Commands;

use App\Services\GalleryFolderService;
use Illuminate\Console\Command;

class SyncGalleryFolders extends Command
{
    protected $signature = 'gallery:sync-folders';
    protected $description = 'Importe les nouvelles photos des lots de galerie activés pour la synchronisation automatique.';

    public function handle(GalleryFolderService $service): int
    {
        $failed = false;
        foreach ($service->lots() as $folder => $lot) {
            if (! ($lot['auto_sync'] ?? false)) {
                continue;
            }
            try {
                $r = $service->sync($folder);
                $this->info($folder.' : '.json_encode($r));
            } catch (\Throwable $e) {
                report($e);
                $this->error($folder.' : import interrompu, consulter le journal.');
                $failed = true;
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
