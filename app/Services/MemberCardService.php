<?php



namespace App\Services;



use App\Models\MemberCard;

use App\Models\Territory;

use App\Models\User;

use Barryvdh\DomPDF\Facade\Pdf;

use Endroid\QrCode\Builder\Builder;

use Endroid\QrCode\ErrorCorrectionLevel;

use Endroid\QrCode\Writer\PngWriter;

use Illuminate\Database\QueryException;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class MemberCardService

{

    public const CATEGORIES = [

        'BP' => 'Bureau de Pilotage',

        'PF' => 'Point focal',

        'CO' => 'Comité d\'organisation',

        'IM' => 'Intervenante / Mentor',

    ];



    public function validityYears(string $category): int

    {

        return $category === 'IM' ? 1 : 3;

    }



    public function verifyUrl(string $token): string

    {

        return url('/carte/verifier/'.$token);

    }



    public function filename(MemberCard $card): string

    {

        return 'carte-'.$card->number.'.pdf';

    }



    public function pdfFor(MemberCard $card): string

    {

        return $this->pdf($this->cardArray($card));

    }



    public function cardArray(MemberCard $card): array

    {

        $card->loadMissing('territory');



        return [

            'name' => $card->full_name,

            'category' => $card->category,

            'category_label' => self::CATEGORIES[$card->category] ?? $card->category,

            'role' => $card->role_label,

            'territory' => $card->territory ? $card->territory->city.' · '.$card->territory->country : 'Siège',

            'number' => $card->number,

            'valid_until' => $card->valid_until,

            'photo' => $this->photoDataUri($card->photo_path),

            'token' => $card->verify_token,

        ];

    }



    /**

     * @param array{source_type: string, source_id: int, category: string, territory_id: ?int, full_name: string, email: ?string, role_label: string} $data

     */

    public function issue(array $data, User $by, ?UploadedFile $photo = null): MemberCard

    {

        $edition = (string) config('jnuaf.edition');

        $category = $data['category'];

        $territory = ! empty($data['territory_id'])

            ? Territory::query()->findOrFail($data['territory_id'])

            : null;



        $token = Str::random(40);

        $photoPath = $photo !== null ? $this->storePhoto($photo, $token) : null;



        try {

            for ($attempt = 1; ; $attempt++) {

                try {

                    return DB::transaction(function () use ($edition, $category, $territory, $data, $token, $photoPath, $by) {

                        $sequence = $this->nextSequence($edition, $category, $territory?->id);



                        return MemberCard::query()->create([

                            'edition' => $edition,

                            'category' => $category,

                            'number' => $this->makeNumber($edition, $territory, $category, $sequence),

                            'sequence' => $sequence,

                            'territory_id' => $territory?->id,

                            'source_type' => $data['source_type'],

                            'source_id' => $data['source_id'],

                            'full_name' => $data['full_name'],

                            'email' => $data['email'] ?? null,

                            'role_label' => $data['role_label'],

                            'photo_path' => $photoPath,

                            'verify_token' => $token,

                            'valid_until' => now()->addYears($this->validityYears($category))->toDateString(),

                            'issued_at' => now(),

                            'issued_by' => $by->id,

                        ]);

                    });

                } catch (QueryException $exception) {

                    if ($attempt >= 3) {

                        throw $exception;

                    }

                }

            }

        } catch (\Throwable $exception) {

            if ($photoPath !== null && is_file($photoPath)) {

                @unlink($photoPath);

            }



            throw $exception;

        }

    }



    /**

     * @param array{name: string, category: string, category_label: string, role: string, territory: string, number: string, valid_until: \Illuminate\Support\Carbon, photo: ?string, token: string} $card

     */

    public function pdf(array $card): string

    {

        return Pdf::loadView('pdf.member-card', [

            'card' => $card,

            'logo' => $this->fileDataUri(public_path('images/logo.jpg'), 'image/jpeg'),

            'signature' => $this->fileDataUri((string) config('jnuaf_signature.file'), 'image/png'),

            'signer' => config('jnuaf_signature'),

            'qr' => $this->qrDataUri($this->verifyUrl($card['token'])),

            'infoUrl' => preg_replace('#^https?://#', '', url('/carte-info')),

        ])->setOption('isFontSubsettingEnabled', true)

            ->setPaper([0, 0, 242.65, 153.02])

            ->output();

    }




    public function replacePhoto(MemberCard $card, UploadedFile $photo): void

    {

        $new = $this->storePhoto($photo, Str::random(40));

        $old = $card->photo_path;



        $card->forceFill(['photo_path' => $new])->save();



        if ($old && is_file($old)) {

            @unlink($old);

        }

    }


    public function sample(string $category): array

    {

        $territory = Territory::query()->orderBy('position')->first();

        $edition = (string) config('jnuaf.edition');



        $roles = [

            'BP' => 'Bureau de Pilotage',

            'PF' => 'Point focal · Coordination territoriale',

            'CO' => 'Membre du comité d\'organisation · Pôle Communication & Médias',

            'IM' => 'Mentor',

        ];



        return [

            'name' => 'Aya Kouassi',

            'category' => $category,

            'category_label' => self::CATEGORIES[$category],

            'role' => $roles[$category],

            'territory' => $territory ? $territory->city.' · '.$territory->country : 'Siège',

            'number' => 'M'.substr($edition, -2).'-'.($territory->code ?? 'HQ').'-'.$category.'-0042',

            'valid_until' => now()->addYears($category === 'IM' ? 1 : 3),

            'photo' => null,

            'token' => Str::random(40),

        ];

    }



    private function nextSequence(string $edition, string $category, ?int $territoryId): int

    {

        $max = MemberCard::query()

            ->where('edition', $edition)

            ->where('category', $category)

            ->when(

                $territoryId !== null,

                fn ($query) => $query->where('territory_id', $territoryId),

                fn ($query) => $query->whereNull('territory_id')

            )

            ->lockForUpdate()

            ->max('sequence');



        return (int) $max + 1;

    }



    private function makeNumber(string $edition, ?Territory $territory, string $category, int $sequence): string

    {

        return 'M'.substr($edition, -2).'-'.($territory->code ?? 'HQ').'-'.$category.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);

    }



    private function storePhoto(UploadedFile $file, string $token): string

    {

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));



        if (! $source) {

            throw new \RuntimeException('Photo illisible.');

        }



        if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {

            $exif = @exif_read_data($file->getRealPath());

            $angle = [3 => 180, 6 => -90, 8 => 90][(int) ($exif['Orientation'] ?? 1)] ?? 0;



            if ($angle !== 0) {

                $rotated = imagerotate($source, $angle, 0);



                if ($rotated) {

                    $source = $rotated;

                }

            }

        }



        $width = imagesx($source);

        $height = imagesy($source);

        $ratio = 3 / 4;



        if ($width / $height > $ratio) {

            $cropW = (int) round($height * $ratio);

            $cropH = $height;

            $cropX = (int) (($width - $cropW) / 2);

            $cropY = 0;

        } else {

            $cropW = $width;

            $cropH = (int) round($width / $ratio);

            $cropX = 0;

            $cropY = (int) (($height - $cropH) / 2);

        }



        $target = imagecreatetruecolor(300, 400);

        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));

        imagecopyresampled($target, $source, 0, 0, $cropX, $cropY, 300, 400, $cropW, $cropH);



        $dir = rtrim((string) config('jnuaf_cards.photos_dir'), '/');



        if (! is_dir($dir) && ! mkdir($dir, 0750, true)) {

            throw new \RuntimeException('Dossier des photos inaccessible.');

        }



        $path = $dir.'/'.$token.'.jpg';

        imagejpeg($target, $path, 85);

        chmod($path, 0640);



        return $path;

    }



    private function photoDataUri(?string $path): ?string

    {

        return $path ? $this->fileDataUri($path, 'image/jpeg') : null;

    }



    private function qrDataUri(string $data): string

    {

        return (new Builder(

            writer: new PngWriter,

            data: $data,

            errorCorrectionLevel: ErrorCorrectionLevel::Medium,

            size: 320,

            margin: 4,

        ))->build()->getDataUri();

    }



    private function fileDataUri(string $path, string $mime): ?string

    {

        if ($path === '' || ! is_file($path)) {

            return null;

        }



        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));

    }

}

