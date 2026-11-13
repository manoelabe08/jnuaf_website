<?php

namespace App\Services;

use App\Models\GalleryPhoto;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class GalleryFolderService
{
    public const BASE = 'images/galerie';

    private function locked(string $name, callable $callback): mixed
    {
        $dir = storage_path('app/private/gallery-sync');
        File::ensureDirectoryExists($dir, 0755, true);
        $handle = fopen($dir.'/'.$name.'.lock', 'c');
        if ($handle === false) {
            throw new RuntimeException('Impossible d’ouvrir le verrou de synchronisation.');
        }
        try {
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Une opération est déjà en cours. Réessayez dans quelques instants.');
            }
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function readSettings(): array
    {
        $file = storage_path('app/private/gallery-sync/lots.json');
        if (! is_file($file)) {
            return [];
        }
        $decoded = json_decode(File::get($file), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($decoded)) {
            throw new RuntimeException('Le fichier des lots est invalide.');
        }
        return $decoded;
    }

    public function lots(): array
    {
        return $this->locked('settings', fn () => $this->readSettings());
    }

    public function saveLot(string $folder, array $data, bool $create): void
    {
        if (! preg_match('/\A[a-z0-9][a-z0-9_-]{0,39}\z/', $folder)
            || in_array($folder, ['old_as_delete', 'old_speakers', 'archives'], true)) {
            throw new RuntimeException('Nom de dossier non autorisé.');
        }
        $this->locked('settings', function () use ($folder, $data, $create) {
            $lots = $this->readSettings();
            if ($create && isset($lots[$folder])) {
                throw new RuntimeException('Ce dossier est déjà configuré. Modifiez le lot existant.');
            }
            if (! $create && ! isset($lots[$folder])) {
                throw new RuntimeException('Lot introuvable.');
            }
            $base = public_path(self::BASE);
            if (is_link($base) || is_link($base.'/'.$folder)) {
                throw new RuntimeException('Les liens symboliques ne sont pas acceptés pour les dossiers importés.');
            }
            File::ensureDirectoryExists($base.'/'.$folder, 0755, true);
            $lots[$folder] = $data;
            $target = storage_path('app/private/gallery-sync/lots.json');
            $temporary = $target.'.'.Str::random(12).'.tmp';
            try {
                if (file_put_contents($temporary, json_encode($lots, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), LOCK_EX) === false
                    || ! rename($temporary, $target)) {
                    throw new RuntimeException('Impossible de sauvegarder les paramètres des lots.');
                }
            } finally {
                if (is_file($temporary)) {
                    @unlink($temporary);
                }
            }
        });
    }

    public function sync(string $folder): array
    {
        $lots = $this->lots();
        if (! isset($lots[$folder])) {
            throw new RuntimeException('Lot introuvable.');
        }
        $lot = $lots[$folder];
        return $this->locked('import', function () use ($folder, $lot) {
            $base = realpath(public_path(self::BASE));
            $dir = public_path(self::BASE.'/'.$folder);
            $real = realpath($dir);
            if ($base === false || $real === false || is_link($dir)
                || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('Dossier du lot absent ou non autorisé.');
            }
            $result = ['added' => 0, 'existing' => 0, 'ignored' => 0, 'waiting' => 0, 'limited' => false];
            $files = File::files($dir);
            usort($files, fn ($a, $b) => strnatcasecmp($a->getFilename(), $b->getFilename()));
            $position = (int) GalleryPhoto::where('edition', $lot['edition'])->max('position');
            foreach ($files as $file) {
                if ($result['added'] >= 100) {
                    $result['limited'] = true;
                    break;
                }
                $name = $file->getFilename();
                $path = self::BASE.'/'.$folder.'/'.$name;
                if ($file->isLink() || ! preg_match('/\A[A-Za-z0-9_][A-Za-z0-9_-]{0,179}\.(jpe?g|png|webp)\z/i', $name)) {
                    $result['ignored']++;
                    continue;
                }
                if (GalleryPhoto::where('path', $path)->exists()) {
                    $result['existing']++;
                    continue;
                }
                clearstatcache(true, $file->getPathname());
                if (time() - $file->getMTime() < 60) {
                    $result['waiting']++;
                    continue;
                }
                if ($file->getSize() < 1 || $file->getSize() > 12 * 1024 * 1024) {
                    $result['ignored']++;
                    continue;
                }
                $meta = @getimagesize($file->getPathname());
                $expected = match (strtolower($file->getExtension())) {
                    'jpg', 'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                    'webp' => 'image/webp',
                    default => null,
                };
                if (! $meta || ($meta['mime'] ?? null) !== $expected) {
                    $result['ignored']++;
                    continue;
                }
                if ($position >= 65535) {
                    throw new RuntimeException('Limite de position atteinte pour cette édition.');
                }
                GalleryPhoto::create([
                    'edition' => $lot['edition'],
                    'title' => $lot['title'],
                    'path' => $path,
                    'size' => $lot['size'] ?? null,
                    'is_published' => (bool) $lot['is_published'],
                    'position' => ++$position,
                ]);
                $result['added']++;
            }
            return $result;
        });
    }
}
