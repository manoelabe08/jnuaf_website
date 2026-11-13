<?php

namespace App\Services;

use App\Models\Speaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SpeakerPhotoService
{
    public const DIRECTORY = 'images/speakers';

    private function directory(string $path): void
    {
        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException('Impossible de créer le dossier des photos.');
        }
    }

    public function save(UploadedFile $file, Speaker $speaker): string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagepng')) {
            throw ValidationException::withMessages([
                'photo' => 'La conversion PNG nécessite l’extension PHP GD sur LWS.',
            ]);
        }

        $info = @getimagesize($file->getRealPath());
        if (! $info || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 12000000) {
            throw ValidationException::withMessages([
                'photo' => 'Image invalide ou trop grande : maximum 12 millions de pixels. Réduisez ses dimensions.',
            ]);
        }

        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['photo' => 'Impossible de lire cette image.']);
        }

        $canvas = null;
        $absolute = null;
        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $side = min($width, $height);
            $outputSize = min(800, $side);
            $canvas = imagecreatetruecolor($outputSize, $outputSize);
            if ($canvas === false) {
                throw new RuntimeException('Impossible de préparer la conversion.');
            }
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
            if (! imagecopyresampled(
                $canvas, $source, 0, 0,
                (int) (($width - $side) / 2), (int) (($height - $side) / 2),
                $outputSize, $outputSize, $side, $side
            )) {
                throw new RuntimeException('Impossible de redimensionner la photo.');
            }

            $dir = public_path(self::DIRECTORY);
            $this->directory($dir);
            $slug = substr(Str::slug($speaker->name), 0, 60) ?: 'intervenant';
            $base = $slug.'-'.$speaker->getKey();
            $name = $base.'.png';
            $version = 2;
            while (file_exists($dir.'/'.$name)) {
                $name = $base.'-'.$version++.'.png';
            }
            $absolute = $dir.'/'.$name;
            if (! imagepng($canvas, $absolute, 6)) {
                throw new RuntimeException('Impossible d’enregistrer la photo PNG.');
            }
            @chmod($absolute, 0644);
            return self::DIRECTORY.'/'.$name;
        } catch (\Throwable $exception) {
            if ($absolute && is_file($absolute)) {
                @unlink($absolute);
            }
            throw $exception;
        } finally {
            imagedestroy($source);
            if ($canvas !== null && $canvas !== false) {
                imagedestroy($canvas);
            }
        }
    }

    public function source(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, self::DIRECTORY.'/')) {
            $relative = substr($path, strlen(self::DIRECTORY) + 1);
            if ($relative === '' || str_contains($relative, '/') || str_contains($relative, '\\')) {
                throw new RuntimeException('Chemin de photo non autorisé.');
            }
            $candidate = public_path($path);
            $base = public_path(self::DIRECTORY);
        } elseif (str_starts_with($path, 'speakers/')) {
            $relative = substr($path, strlen('speakers/'));
            if ($relative === '' || str_contains($relative, '/') || str_contains($relative, '\\')) {
                throw new RuntimeException('Ancien chemin de photo non autorisé.');
            }
            $candidate = Storage::disk('public')->path($path);
            $base = Storage::disk('public')->path('speakers');
        } else {
            throw new RuntimeException('Chemin de photo inconnu : opération arrêtée pour protéger le fichier.');
        }
        if (! is_file($candidate)) {
            return null;
        }
        $real = realpath($candidate);
        $realBase = realpath($base);
        if ($real === false || $realBase === false || ! str_starts_with($real, $realBase.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Chemin de photo hors du dossier autorisé.');
        }
        return $real;
    }

    public function backup(?string $path): ?string
    {
        $source = $this->source($path);
        if ($source === null) {
            return null;
        }
        $trash = public_path(self::DIRECTORY.'/old_speakers');
        $this->directory($trash);
        if (file_put_contents($trash.'/.htaccess', "Require all denied\n", LOCK_EX) === false) {
            throw new RuntimeException('Impossible de protéger le dossier des archives.');
        }
        $destination = $trash.'/'.date('Ymd-His').'-'.Str::lower(Str::random(8)).'-'.basename($source);
        if (! copy($source, $destination)) {
            throw new RuntimeException('Archivage impossible : la photo originale est conservée.');
        }
        if (hash_file('sha256', $source) !== hash_file('sha256', $destination)) {
            throw new RuntimeException('Copie d’archive non vérifiée : la photo originale est conservée.');
        }
        @chmod($destination, 0644);
        return $source;
    }

    public function removeOriginal(?string $absolute): void
    {
        if ($absolute && is_file($absolute) && ! @unlink($absolute)) {
            report(new RuntimeException('Photo archivée, mais original non supprimé : '.$absolute));
        }
    }
}
