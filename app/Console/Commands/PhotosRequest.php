<?php



namespace App\Console\Commands;



use App\Mail\PhotoRequestMail;

use App\Services\PhotoCollectionService;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;



class PhotosRequest extends Command

{

    protected $signature = 'photos:request {--scope=all : all, users, committee ou cards} {--dry-run : Liste sans envoyer} {--limit=40} {--resend} {--only= : Adresse unique (test)}';



    protected $description = 'Envoie un lien personnel pour deposer la photo (profil, candidature, carte)';



    public function handle(PhotoCollectionService $svc): int

    {

        $scope = (string) $this->option('scope');

        if (! in_array($scope, ['all', 'users', 'committee', 'cards', 'speakers'], true)) {

            $this->error('Portee invalide.');



            return self::FAILURE;

        }

        $only = mb_strtolower(trim((string) $this->option('only')));

        if ($only !== '') {

            $targets = [$only => ''];

        } else {

            $targets = $svc->targets($scope);

            if (! $this->option('resend')) {

                $recent = DB::table('photo_requests')->whereNull('used_at')->where('expires_at', '>', now())

                    ->where('created_at', '>', now()->subHours(72))->pluck('email')->map(fn ($m) => mb_strtolower($m))->flip()->all();

                $targets = array_diff_key($targets, $recent);

            }

        }

        $total = count($targets);

        $this->info("Destinataires a relancer : $total");

        if ($this->option('dry-run')) {

            $rows = [];

            foreach (array_slice($targets, 0, 150, true) as $email => $name) {

                $rows[] = [$email, $name];

            }

            $this->table(['Email', 'Nom'], $rows);

            $this->line('Exemple de lien : '.url('/photo/EXEMPLE'));



            return self::SUCCESS;

        }

        $limit = max(1, (int) $this->option('limit'));

        $sent = 0;

        $failed = 0;

        foreach (array_slice($targets, 0, $limit, true) as $email => $name) {

            $raw = $svc->createRequest($email, $name);

            $first = $svc->givenName($name);

            try {

                Mail::mailer('team')->to($email)->send(new PhotoRequestMail($first, url('/photo/'.$raw), PhotoCollectionService::DAYS));

                DB::table('photo_requests')->where('token_hash', hash('sha256', $raw))->update(['sent_at' => now()]);

                $sent++;

            } catch (\Throwable $ex) {

                report($ex);

                DB::table('photo_requests')->where('token_hash', hash('sha256', $raw))->delete();

                $failed++;

            }

            usleep(400000);

        }

        $this->info("Envoyes : $sent | Echecs : $failed | Restants : ".max(0, $total - $sent - $failed));



        return self::SUCCESS;

    }

}

