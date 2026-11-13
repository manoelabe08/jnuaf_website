<?php



namespace App\Console\Commands;



use App\Mail\AdminAlert;

use App\Services\AdminMailboxService;

use App\Services\AdminNotifier;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Route;



class NotifyNewMailboxMessages extends Command

{

    protected $signature = 'mailbox:notify-new {--force : Ignore l’intervalle minimal entre deux vérifications} {--dry : N’envoie rien et ne mémorise rien}';

    protected $description = 'Prévient les administrateurs quand de nouveaux messages non lus arrivent dans la boîte de l’équipe';



    private const KEY = 'mailbox:notify:last-uid';

    private const GATE = 'mailbox:notify:gate';



    public function handle(AdminMailboxService $mailbox, AdminNotifier $notifier): int

    {

        if (! $this->option('force') && ! $this->option('dry')

            && ! Cache::add(self::GATE, now()->toDateTimeString(), now()->addMinutes(4))) {

            return self::SUCCESS;

        }



        try {

            $unread = $mailbox->search('INBOX', ['filter' => 'unread'], 20);

        } catch (\Throwable $exception) {

            report($exception);

            $this->warn('Boîte injoignable : '.class_basename($exception));



            return self::SUCCESS;

        } finally {

            try {

                $mailbox->disconnect();

            } catch (\Throwable) {

            }

        }



        $uids = array_map(fn ($message) => (int) $message['uid'], $unread);

        $maxUid = $uids === [] ? 0 : max($uids);

        $last = Cache::get(self::KEY);



        if ($last === null) {

            $this->info('Initialisation : '.count($uids).' message(s) non lu(s) déjà présent(s), aucune alerte envoyée.');

            if (! $this->option('dry')) {

                Cache::forever(self::KEY, $maxUid);

            }



            return self::SUCCESS;

        }



        $new = array_values(array_filter($uids, fn ($uid) => $uid > (int) $last));

        $this->info(count($new).' nouveau(x) message(s) depuis le dernier passage.');



        if ($new === []) {

            return self::SUCCESS;

        }



        if ($this->option('dry')) {

            $this->line('Simulation : une alerte serait envoyée aux administrateurs.');



            return self::SUCCESS;

        }



        $count = count($new);

        $notifier->send(new AdminAlert(

            title: $count === 1 ? 'Nouveau message dans la boîte de l’équipe' : $count.' nouveaux messages dans la boîte de l’équipe',

            intro: 'Du nouveau courrier attend dans la messagerie de l’équipe. Par confidentialité, cet email n’indique ni l’expéditeur ni le sujet.',

            details: [

                'Boîte' => (string) config('admin_mailbox.imap.username'),

                'Nouveaux messages non lus' => (string) $count,

            ],

            url: Route::has('admin.mailbox.inbox') ? route('admin.mailbox.inbox') : url('/admin'),

            action: 'Ouvrir la messagerie',

        ));



        Cache::forever(self::KEY, max((int) $last, $maxUid));



        return self::SUCCESS;

    }

}

