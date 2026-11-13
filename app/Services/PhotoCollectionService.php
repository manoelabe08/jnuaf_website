<?php



namespace App\Services;



use App\Models\CommitteeApplication;

use App\Models\MemberCard;
use App\Models\Registration;

use App\Models\User;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;



class PhotoCollectionService

{

    public const DAYS = 14;

    private const SPEAKER_PROFILES = ['mentor', 'intervenante'];



    public function targets(string $scope = 'all'): array

    {

        $out = [];

        $add = function (?string $email, ?string $name) use (&$out) {

            $email = mb_strtolower(trim((string) $email));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with($email, '.invalid')) {

                return;

            }

            if (! isset($out[$email])) {

                $out[$email] = trim((string) $name);

            }

        };

        if (in_array($scope, ['all', 'users'], true)) {

            foreach (User::whereNull('avatar_path')->whereNull('suspended_at')->whereNull('anonymized_at')->get(['name', 'email']) as $u) {

                $add($u->email, $u->name);

            }

        }

        if (in_array($scope, ['all', 'committee'], true)) {

            foreach (CommitteeApplication::where('status', 'accepted')->whereNull('photo_path')->get(['full_name', 'email']) as $a) {

                $add($a->email, $a->full_name);

            }

        }

        if (in_array($scope, ['all', 'cards'], true)) {

            foreach (MemberCard::whereNull('photo_path')->whereNull('revoked_at')->whereNull('anonymized_at')->whereDate('valid_until', '>=', now()->toDateString())->get(['full_name', 'email']) as $c) {

                $add($c->email, $c->full_name);

            }

        }



        
if (in_array($scope, ['all', 'speakers'], true)) {

            foreach (Registration::whereIn('profile', self::SPEAKER_PROFILES)->where('edition', config('jnuaf.edition'))->whereNull('photo_path')->whereNull('anonymized_at')->where('status', '!=', 'cancelled')->get(['first_name', 'last_name', 'email']) as $r) {

                $add($r->email, trim($r->first_name.' '.$r->last_name));

            }

        }



        return $out;


    }





    public function registrationRoot(): string

    {

        $root = rtrim((string) config('partner_portal.private_root'), '/\\');

        if ($root === '') {

            throw new \RuntimeException('PARTNER_PRIVATE_ROOT non configure.');

        }



        return $root.'/registration-files';

    }



    private function ensureDir(string $dir): void

    {

        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {

            throw new \RuntimeException('Dossier des fichiers inaccessible.');

        }

    }



    public function storeRegistrationPhoto(Registration $registration, string $tmp): string

    {

        $dir = $this->registrationRoot();

        $this->ensureDir($dir);

        $name = $registration->id.'-'.Str::lower(Str::random(24)).'.jpg';

        copy($tmp, $dir.'/'.$name);

        chmod($dir.'/'.$name, 0600);



        return $name;

    }



    public function storeRegistrationCv(Registration $registration, UploadedFile $cv): string

    {

        $path = $cv->getRealPath();

        if (file_get_contents($path, false, null, 0, 5) !== '%PDF-') {

            throw ValidationException::withMessages(['cv' => "Le CV doit \u{ea}tre un vrai fichier PDF."]);

        }

        $dir = $this->registrationRoot();

        $this->ensureDir($dir);

        $name = $registration->id.'-'.Str::lower(Str::random(24)).'.pdf';

        copy($path, $dir.'/'.$name);

        chmod($dir.'/'.$name, 0600);



        return $name;

    }



    private function removeRegistrationFile(?string $name): void

    {

        if ($name && preg_match('/^\d+-[a-z0-9]{24}\.(jpg|pdf)$/', $name) === 1) {

            $file = $this->registrationRoot().'/'.$name;

            if (is_file($file)) {

                @unlink($file);

            }

        }

    }



    
    public function deleteRegistrationFiles(Registration $registration): void

    {

        $this->removeRegistrationFile($registration->photo_path);

        $this->removeRegistrationFile($registration->cv_path);

        $registration->forceFill(['photo_path' => null, 'cv_path' => null]);

    }

    public function needsCv(string $email): bool

    {

        return Registration::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->whereNull('anonymized_at')

            ->whereIn('profile', self::SPEAKER_PROFILES)->whereNull('cv_path')->exists();

    }



    public function applySpeakers(string $email, ?string $photoTmp, ?UploadedFile $cv, bool $replace = false): array

    {

        $done = [];

        $regs = Registration::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email))])->whereNull('anonymized_at')

            ->whereIn('profile', self::SPEAKER_PROFILES)->get();

        foreach ($regs as $reg) {

            $updates = [];

            if ($photoTmp && ($replace || ! $reg->photo_path)) {

                $old = $reg->photo_path;

                $updates['photo_path'] = $this->storeRegistrationPhoto($reg, $photoTmp);

                $this->removeRegistrationFile($old);

                $done[] = 'photo intervenant(e)';

            }

            if ($cv && ($replace || ! $reg->cv_path)) {

                $old = $reg->cv_path;

                $updates['cv_path'] = $this->storeRegistrationCv($reg, $cv);

                $this->removeRegistrationFile($old);

                $done[] = 'CV';

            }

            if ($updates) {

                $reg->forceFill($updates)->save();

            }

        }



        return $done;

    }




    public function givenName(?string $name): string

    {

        $tokens = preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $given = array_values(array_filter($tokens, fn ($t) => mb_strlen($t) < 2 || $t !== mb_strtoupper($t)));



        return (string) ($given[0] ?? ($tokens[0] ?? ''));

    }




    public function createRequest(string $email, ?string $name, bool $replace = false, ?string $reason = null): string

    {

        $raw = Str::random(48);

        DB::table('photo_requests')->insert([

            'email' => $email,

            'name' => mb_substr((string) $name, 0, 190),
            'replace' => $replace,
            'reason' => $reason ? mb_substr($reason, 0, 300) : null,

            'token_hash' => hash('sha256', $raw),

            'expires_at' => now()->addDays(self::DAYS),

            'created_at' => now(),

            'updated_at' => now(),

        ]);



        return $raw;

    }



    public function find(string $raw): ?object

    {

        return DB::table('photo_requests')->where('token_hash', hash('sha256', $raw))

            ->whereNull('used_at')->where('expires_at', '>', now())->first();

    }



    public function markUsed(int $id): void

    {

        DB::table('photo_requests')->where('id', $id)->update(['used_at' => now(), 'updated_at' => now()]);

    }



    public function committeeRoot(): string

    {

        $root = rtrim((string) config('partner_portal.private_root'), '/\\');

        if ($root === '') {

            throw new \RuntimeException('PARTNER_PRIVATE_ROOT non configure.');

        }



        return $root.'/committee-photos';

    }



    public function normalize(UploadedFile $file): string

    {

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {

            throw ValidationException::withMessages(['photo' => "Le traitement d'image n'est pas disponible sur le serveur."]);

        }

        $path = $file->getRealPath();

        $info = @getimagesize($path);

        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true) || $info[0] * $info[1] > 40000000) {

            throw ValidationException::withMessages(['photo' => "Image invalide : JPG, PNG ou WebP uniquement."]);

        }

        if ($info[0] < 250 || $info[1] < 300) {

            throw ValidationException::withMessages(['photo' => "Photo trop petite : 250 x 300 pixels au minimum."]);

        }

        $src = @imagecreatefromstring((string) file_get_contents($path));

        if (! $src) {

            throw ValidationException::withMessages(['photo' => "Impossible de lire cette image."]);

        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {

            $exif = @exif_read_data($path);

            $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;

            if ($angle) {

                $rot = imagerotate($src, $angle, 0);

                if ($rot) {

                    imagedestroy($src);

                    $src = $rot;

                }

            }

        }$w = imagesx($src);

        $h = imagesy($src);

        $scale = min(1, 1000 / max($w, $h));

        $nw = max(1, (int) round($w * $scale));

        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);

        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $tmp = tempnam(sys_get_temp_dir(), 'jph');

        $ok = imagejpeg($dst, $tmp, 88);

        imagedestroy($src);

        imagedestroy($dst);

        if (! $ok) {

            throw ValidationException::withMessages(['photo' => "Enregistrement de la photo impossible."]);

        }



        return $tmp;

    }



    public function fresh(string $tmp): UploadedFile

    {

        $copy = tempnam(sys_get_temp_dir(), 'jph');

        copy($tmp, $copy);



        return new UploadedFile($copy, 'photo.jpg', 'image/jpeg', null, true);

    }



    public function apply(string $email, string $tmp, bool $replace = false): array

    {

        $done = [];

        $email = mb_strtolower(trim($email));

        $user = User::whereRaw('LOWER(email) = ?', [$email])->whereNull('anonymized_at')->first();

        if ($user && ($replace || ! $user->avatar_path)) {

            $name = app(AvatarService::class)->save($user, $this->fresh($tmp));

            $old = $user->avatar_path;
            $user->forceFill(['avatar_path' => $name])->save();
            if ($old && $old !== $name) {
                app(AvatarService::class)->delete($old);
            }

            $done[] = 'profil';

        }

        foreach (CommitteeApplication::whereRaw('LOWER(email) = ?', [$email])->where('status', 'accepted')->when(! $replace, fn ($q) => $q->whereNull('photo_path'))->get() as $app) {

            $dir = $this->committeeRoot();

            if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {

                throw new \RuntimeException('Dossier des photos inaccessible.');

            }

            $name = $app->id.'-'.Str::lower(Str::random(24)).'.jpg';

            copy($tmp, $dir.'/'.$name);

            chmod($dir.'/'.$name, 0600);

            $oldPhoto = $app->photo_path;
            $app->forceFill(['photo_path' => $name, 'photo_consented_at' => now(), 'photo_rejected_at' => null, 'photo_reject_reason' => null])->save();
            if ($oldPhoto && $oldPhoto !== $name && preg_match('/^[0-9]+-[a-z0-9]{24}\.jpg$/', $oldPhoto) === 1) {
                @unlink($dir.'/'.$oldPhoto);
            }

            $done[] = 'candidature';

        }

        foreach (MemberCard::whereRaw('LOWER(email) = ?', [$email])->when(! $replace, fn ($q) => $q->whereNull('photo_path'))->whereNull('revoked_at')->whereNull('anonymized_at')->get() as $card) {

            app(MemberCardService::class)->replacePhoto($card, $this->fresh($tmp));

            $done[] = 'carte '.$card->number;

        }



        return $done;

    }

}

