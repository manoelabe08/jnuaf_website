<?php



namespace App\Console\Commands;



use App\Enums\ApplicationStatus;

use App\Mail\MemberCardMessage;

use App\Models\CommitteeApplication;

use App\Models\MemberCard;

use App\Models\User;

use App\Services\MemberCardService;

use App\Services\PhotoCollectionService;

use Illuminate\Console\Command;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;



class AutoIssueCards extends Command

{

    protected $signature = 'cards:auto-issue {--dry-run : Affiche ce qui serait fait} {--backlog : Inclut les candidatures acceptees avant l activation} {--limit=10 : Cartes maximum par passage}';



    protected $description = "Emet et envoie automatiquement la carte des candidatures retenues au comite";



    public function handle(MemberCardService $cards, PhotoCollectionService $photos): int

    {

        if (is_file(storage_path('app/auto-cards.off'))) {

            $this->info('Emission automatique suspendue (fichier auto-cards.off).');



            return self::SUCCESS;

        }

        $sinceFile = storage_path('app/auto-cards.since');

        $since = is_file($sinceFile) ? Carbon::parse(trim((string) file_get_contents($sinceFile)))->setTimezone(config('app.timezone')) : null;

        if ($since === null && ! $this->option('backlog')) {

            $this->warn('Fichier auto-cards.since absent : rien n est traite.');



            return self::SUCCESS;

        }

        $dry = (bool) $this->option('dry-run');

        $limit = max(1, (int) $this->option('limit'));

        $issuer = User::query()->orderBy('id')->first();

        if ($issuer === null) {

            $this->error('Aucun compte emetteur.');



            return self::FAILURE;

        }



        $issued = [];

        $errors = [];

        $waiting = 0;



        if (! $dry) {

            foreach ((array) Cache::get('auto_cards_retry', []) as $cardId) {

                $card = MemberCard::find($cardId);

                if ($card === null || $card->sent_at !== null || ! $card->isActive()) {

                    $this->forgetRetry((int) $cardId);



                    continue;

                }

                try {

                    $this->deliver($card, $cards);

                    $this->forgetRetry((int) $card->id);

                    $issued[] = $card->full_name.' ('.$card->number.') - envoi retente';

                } catch (\Throwable $exception) {

                    report($exception);

                    $errors[] = 'Envoi encore en echec pour '.$card->full_name.' ('.$card->number.')';

                }

            }

        }



        $already = MemberCard::query()->where('source_type', 'committee')->pluck('source_id')->map(fn ($v) => (int) $v)->all();

        $query = CommitteeApplication::query()->where('status', ApplicationStatus::Accepted)->orderBy('id');

        if (! $this->option('backlog')) {

            $query->where(fn ($w) => $w->where('reviewed_at', '>=', $since)->orWhere('updated_at', '>=', $since));

        }

        $todo = $query->get()->reject(fn ($app) => in_array((int) $app->id, $already, true));



        foreach ($todo as $app) {

            if (! filter_var((string) $app->email, FILTER_VALIDATE_EMAIL)) {

                $errors[] = '#'.$app->id.' '.$app->full_name.' : adresse email invalide';



                continue;

            }

            $photo = $this->storedPhoto($app, $photos);

            if ($photo === null) {

                $waiting++;



                continue;

            }

            if (count($issued) >= $limit) {

                break;

            }

            if ($dry) {

                $this->line('Emettrait : '.$app->full_name.' <'.$app->email.'>');

                $issued[] = $app->full_name;



                continue;

            }

            try {

                $card = $cards->issue([

                    'source_type' => 'committee',

                    'source_id' => $app->id,

                    'category' => 'CO',

                    'territory_id' => null,

                    'full_name' => (string) $app->full_name,

                    'email' => mb_strtolower(trim((string) $app->email)),

                    'role_label' => "Membre du comit\u{e9} d'organisation".($app->pole ? ' - '.$app->pole : ''),

                ], $issuer, $photo);

            } catch (\Throwable $exception) {

                report($exception);

                $errors[] = '#'.$app->id.' '.$app->full_name.' : emission impossible ('.mb_substr($exception->getMessage(), 0, 160).')';



                continue;

            }

            try {

                $this->deliver($card, $cards);

                $issued[] = $app->full_name.' ('.$card->number.')';

            } catch (\Throwable $exception) {

                report($exception);

                $retry = (array) Cache::get('auto_cards_retry', []);

                $retry[] = $card->id;

                Cache::put('auto_cards_retry', array_values(array_unique($retry)), now()->addDays(7));

                $errors[] = $app->full_name.' ('.$card->number.') : carte emise, envoi du mail en echec (nouvel essai automatique)';

            }

        }



        $this->info(sprintf('Cartes : %d | en attente de photo : %d | erreurs : %d', count($issued), $waiting, count($errors)));



        if (! $dry && $waiting > 0) {

            Artisan::call('photos:request', ['--scope' => 'committee', '--limit' => 20]);

        }

        $to = config('jnuaf_audit.account_audit_email');

        if (! $dry && $to && ($issued || $errors)) {

            $lines = ["Emission automatique des cartes de membre.", ''];

            foreach ($issued as $line) {

                $lines[] = 'Emise : '.$line;

            }

            foreach ($errors as $line) {

                $lines[] = 'A verifier : '.$line;

            }

            $lines[] = '';

            $lines[] = 'En attente de photo : '.$waiting;

            try {

                Mail::mailer('team')->raw(implode("\n", $lines), function ($m) use ($to) {

                    $m->from('team@jnuaf.org', 'JNUAF')->to($to)->subject("JNUAF : cartes de membre \u{e9}mises automatiquement");

                });

            } catch (\Throwable $exception) {

                report($exception);

            }

        }

        Log::info('Emission automatique des cartes', ['emises' => count($issued), 'attente_photo' => $waiting, 'erreurs' => count($errors)]);



        return self::SUCCESS;

    }



    private function deliver(MemberCard $card, MemberCardService $cards): void

    {

        Mail::mailer('team')->to($card->email)->send(new MemberCardMessage($card, $cards->pdfFor($card), $cards->filename($card)));

        $card->forceFill(['sent_at' => now()])->save();

    }



    private function forgetRetry(int $id): void

    {

        $retry = array_values(array_diff((array) Cache::get('auto_cards_retry', []), [$id]));

        Cache::put('auto_cards_retry', $retry, now()->addDays(7));

    }



    private function storedPhoto(CommitteeApplication $app, PhotoCollectionService $photos): ?UploadedFile

    {

        $name = (string) $app->photo_path;

        if (preg_match('/^\d+-[a-z0-9]{24}\.jpg$/', $name) !== 1) {

            return null;

        }

        $file = $photos->committeeRoot().'/'.$name;

        if (! is_file($file)) {

            return null;

        }

        $tmp = tempnam(sys_get_temp_dir(), 'jnuaf-card-');

        copy($file, $tmp);

        register_shutdown_function(static function () use ($tmp) {

            if (is_file($tmp)) {

                @unlink($tmp);

            }

        });



        return new UploadedFile($tmp, 'photo.jpg', 'image/jpeg', null, true);

    }

}

