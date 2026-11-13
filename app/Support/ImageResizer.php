<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recadre au centre et redimensionne une image téléversée (GD), puis l'enregistre en JPEG
 * sur le disque public. Évite d'afficher sur le site des photos de téléphone de plusieurs Mo.
 */
class ImageResizer
{
    public static function squareJpeg(UploadedFile $file, string $directory, int $size = 600, int $quality = 85): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        // Format non pris en charge par GD : on conserve le fichier tel quel plutôt que d'échouer.
        if ($source === false) {
            return $file->store($directory, 'public');
        }

        $source = self::applyExifOrientation($source, $file);

        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);
        $target = min($size, $side);

        $canvas = imagecreatetruecolor($target, $target);
        // Fond blanc : les PNG transparents ne deviennent pas noirs en JPEG.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, (int) (($width - $side) / 2), (int) (($height - $side) / 2), $target, $target, $side, $side);

        ob_start();
        imagejpeg($canvas, null, $quality);
        $jpeg = (string) ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);

        $path = trim($directory, '/').'/'.Str::random(32).'.jpg';
        Storage::disk('public')->put($path, $jpeg);

        return $path;
    }

    /** Les photos prises au téléphone sont souvent stockées pivotées, avec l'orientation dans l'EXIF. */
    private static function applyExifOrientation(\GdImage $image, UploadedFile $file): \GdImage
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);

        return match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
