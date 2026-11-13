<?php



namespace App\Services;



use App\Models\User;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

use RuntimeException;



class AvatarService

{

    public function root(): string

    {

        $root = rtrim((string) config('partner_portal.private_root'), '/');

        if ($root === '') {

            throw new RuntimeException('PARTNER_PRIVATE_ROOT non configuré.');

        }



        return $root.'/avatars';

    }



    public function save(User $user, UploadedFile $file): string

    {

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagewebp')) {

            throw ValidationException::withMessages(['photo' => 'Le traitement d’image (GD/WebP) n’est pas disponible sur le serveur.']);

        }



        $info = @getimagesize($file->getRealPath());

        $allowed = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

        if (! $info || ! in_array($info[2], $allowed, true) || $info[0] < 64 || $info[1] < 64 || $info[0] * $info[1] > 12000000) {

            throw ValidationException::withMessages(['photo' => 'Image invalide : JPG, PNG ou WebP, au moins 64 px, 12 millions de pixels maximum.']);

        }



        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($source === false) {

            throw ValidationException::withMessages(['photo' => 'Impossible de lire cette image.']);

        }



        $canvas = null;

        $absolute = null;



        try {

            $width = imagesx($source);

            $height = imagesy($source);

            $side = min($width, $height);

            $out = min(320, $side);



            $canvas = imagecreatetruecolor($out, $out);

            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));

            imagecopyresampled(

                $canvas, $source, 0, 0,

                (int) (($width - $side) / 2), (int) (($height - $side) / 2),

                $out, $out, $side, $side

            );



            $dir = $this->root();

            if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {

                throw new RuntimeException('Dossier des photos de profil inaccessible.');

            }



            $name = $user->id.'-'.Str::lower(Str::random(24)).'.webp';

            $absolute = $dir.'/'.$name;

            if (! imagewebp($canvas, $absolute, 85)) {

                throw new RuntimeException('Enregistrement de la photo impossible.');

            }

            @chmod($absolute, 0600);



            return $name;

        } catch (\Throwable $exception) {

            if ($absolute && is_file($absolute)) {

                @unlink($absolute);

            }

            throw $exception;

        } finally {

            

            if ($canvas) {

                

            }

        }

    }



    public function path(?string $name): ?string

    {

        if (! $name || ! preg_match('/^\d+-[a-z0-9]{24}\.webp$/', $name)) {

            return null;

        }



        $base = realpath($this->root());

        $real = realpath($this->root().'/'.$name);



        if (! $base || ! $real || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR) || ! is_file($real)) {

            return null;

        }



        return $real;

    }



    public function delete(?string $name): void

    {

        $path = $this->path($name);

        if ($path) {

            @unlink($path);

        }

    }

}

