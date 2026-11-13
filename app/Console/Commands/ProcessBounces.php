<?php



namespace App\Console\Commands;



use App\Mail\AdminAlert;

use App\Models\NewsletterSubscriber;

use App\Services\AdminNotifier;

use App\Services\BounceMailbox;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Str;



class ProcessBounces extends Command

{

    protected $signature = 'bounces:process {--dry : Simulation, aucune ecriture} {--days=3 : Fenetre de recherche en jours} {--limit=50 : Nombre maximal de rapports traites}';



    protected $description = 'Lit les rapports de non-remise de la boite equipe et bloque les adresses inexistantes';



    private const SOFT_LIMIT = 3;



    private const SOFT_DAYS = 30;



    public function handle(BounceMailbox $mailbox): int

    {

        $dry = (bool) $this->option('dry');

        $days = max(1, (int) $this->option('days'));

        $limit = max(1, min(200, (int) $this->option('limit')));



        try {

            $messages = $mailbox->search('INBOX', ['period' => $days], 200);

        } catch (\Throwable $e) {

            report($e);

            $this->warn('Boite injoignable : '.class_basename($e));



            return self::SUCCESS;

        }



        $candidates = array_values(array_filter($messages, function ($m) {

            $from = strtolower((string) $m['from']);

            $subject = strtolower((string) $m['subject']);



            return str_contains($from, 'mailer-daemon') || str_contains($from, 'postmaster')

                || preg_match('/undelivered|undeliverable|delivery status|delivery failure|returned mail|mail delivery failed|failure notice|non remis|non delivr/', $subject) === 1;

        }));



        $stats = ['hard' => 0, 'soft' => 0, 'policy' => 0, 'unknown' => 0, 'ignored' => 0, 'blocked' => 0, 'registrations' => 0];

        $done = 0;



        foreach ($candidates as $m) {

            if ($done >= $limit) {

                break;

            }

            $uid = (int) $m['uid'];

            if (DB::table('mail_bounces')->where('mailbox_uid', $uid)->exists()) {

                continue;

            }

            $done++;



            try {

                $data = $mailbox->fetch($uid);

            } catch (\Throwable $e) {

                report($e);

                $this->warn('uid '.$uid.' : lecture impossible ('.class_basename($e).')');



                continue;

            }

            if ($data === null) {

                continue;

            }



            $records = $this->parse($data); $keepUnread = false;

            if ($records === []) {

                $stats['unknown']++;

                $this->line('uid '.$uid.' : rapport non reconnu (laisse non lu)');

                if (! $dry) {

                    $this->store($uid, '(aucun)', 'unknown', '', 'Rapport non reconnu');

                }



                continue;

            }



            foreach ($records as [$email, $action, $status, $diag]) {

                $kind = $this->classify($action, $status, $diag);

                if ($kind === 'ignore') {

                    $stats['ignored']++;

                    if (! $dry) {

                        $this->store($uid, $email, 'ignored', $status, $diag);

                    }



                    continue;

                }

                $stats[$kind]++; if ($kind === 'unknown') { $keepUnread = true; }

                $block = $kind === 'hard';

                $note = trim($status.' '.$diag);



                if ($kind === 'soft') {

                    $prior = DB::table('mail_bounces')

                        ->where('email', $email)->where('kind', 'soft')

                        ->where('created_at', '>=', now()->subDays(self::SOFT_DAYS))

                        ->where('mailbox_uid', '!=', $uid)->count();

                    if ($prior + 1 >= self::SOFT_LIMIT) {

                        $block = true;

                        $note = 'Echecs temporaires repetes : '.$note;

                    }

                }



                $subscriber = NewsletterSubscriber::where('email', $email)->first();

                $appliedId = null;

                if ($block && $subscriber && in_array($subscriber->status, ['subscribed', 'pending'], true)) {

                    $appliedId = (int) $subscriber->id;

                    $stats['blocked']++;

                    if (! $dry) {

                        DB::transaction(function () use ($subscriber, $note) {

                            $locked = NewsletterSubscriber::lockForUpdate()->find($subscriber->id);

                            if (! $locked || ! in_array($locked->status, ['subscribed', 'pending'], true)) {

                                return;

                            }

                            $locked->forceFill([

                                'status' => 'bounced',

                                'bounced_at' => now(),

                                'bounce_reason' => Str::limit($note, 250, ''),

                            ])->save();

                            DB::table('newsletter_deliveries')

                                ->where('newsletter_id', $locked->id)->where('status', 'pending')

                                ->update(['status' => 'skipped', 'error' => 'Adresse inexistante (rebond)', 'updated_at' => now()]);

                        });

                    }

                }



                $registration = $block

                    ? DB::table('registrations')->where('email', $email)->whereNull('anonymized_at')->value('id')

                    : null;

                if ($registration) {

                    $stats['registrations']++;

                }



                $this->line(($dry ? '[SIMULATION] ' : '').'uid '.$uid.' '.$this->mask($email).' type='.$kind

                    .' code='.($status !== '' ? $status : '-').($dry ? ' diag='.Str::limit((string) preg_replace('/[^\s<>]+@[^\s<>]+/', '***', $diag), 80, '') : '')

                    .($block ? ' -> BLOQUEE' : '')

                    .($registration ? ' (inscription #'.$registration.')' : ''));



                if (! $dry) {

                    $this->store($uid, $email, $kind, $status, $diag, $subscriber ? 'newsletter' : null, $appliedId);

                }

            }



            if (! $dry) {

                try {

                    if (! $keepUnread) { $mailbox->markSeen($uid); }

                } catch (\Throwable $e) {

                    report($e);

                }

            }

        }



        try {

            $mailbox->disconnect();

        } catch (\Throwable) {

        }



        $this->info('Rapports examines : '.$done.' | definitifs : '.$stats['hard'].' | temporaires : '.$stats['soft']

            .' | politique : '.$stats['policy'].' | inconnus : '.$stats['unknown'].' | ignores : '.$stats['ignored']

            .' | abonnes bloques : '.$stats['blocked'].' | inscriptions concernees : '.$stats['registrations']);



        if (! $dry && ($stats['blocked'] > 0 || $stats['policy'] > 0 || $stats['registrations'] > 0)) {

            try {

                app(AdminNotifier::class)->send(new AdminAlert(

                    title: "Adresses en \u{e9}chec de livraison",

                    intro: "Des messages n'ont pas pu \u{ea}tre livr\u{e9}s. Les adresses inexistantes sont bloqu\u{e9}es pour les prochains envois. Par confidentialit\u{e9}, aucune adresse n'est indiqu\u{e9}e ici.",

                    details: [

                        "Adresses bloqu\u{e9}es" => (string) $stats['blocked'],

                        "Rejets li\u{e9}s \u{e0} la r\u{e9}putation" => (string) $stats['policy'],

                        "Inscriptions concern\u{e9}es" => (string) $stats['registrations'],

                    ],

                    url: Route::has('admin.newsletter.subscribers') ? route('admin.newsletter.subscribers', ['status' => 'bounced']) : url('/admin'),

                    action: "Voir les adresses",

                ));

            } catch (\Throwable $e) {

                report($e);

            }

        }



        return self::SUCCESS;

    }




    private function parse(array $data): array

    {

        $records = [];

        $blob = '';

        foreach ($data['parts'] as $part) {

            $content = str_replace("\r", '', $part['content']);

            if (str_contains($part['mime'], 'delivery-status') || preg_match('/^(Reporting-MTA|Final-Recipient)\s*:/mi', $content)) {

                $blob .= "\n\n".$content;

            }

        }

        if ($blob !== '') {

            foreach (preg_split('/\n{2,}/', $blob) as $block) {

                if (! preg_match('/^Final-Recipient:\s*[a-z0-9-]+;\s*<?([^>\s]+)>?/mi', $block, $r)) {

                    continue;

                }

                preg_match('/^Action:\s*([a-z]+)/mi', $block, $a);

                preg_match('/^Status:\s*([0-9]\.[0-9]{1,3}\.[0-9]{1,3})/mi', $block, $s);

                preg_match('/^Diagnostic-Code:\s*(.+(?:\n[ \t]+.+)*)/mi', $block, $d);

                $email = strtolower(trim($r[1]));

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    $diagText = trim((string) preg_replace('/\s+/', ' ', $d[1] ?? ''));
                    $code = $s[1] ?? '';
                    if (preg_match('/\b([45]\.[0-9]{1,3}\.[0-9]{1,3})\b/', $diagText, $dm)) {
                        $code = $dm[1];
                    }
                    $records[] = [$email, strtolower($a[1] ?? ''), $code, $diagText];

                }

            }

        }

        if ($records === []) {

            $text = str_replace("\r", '', $data['text']);

            if (preg_match_all('/<([^<>\s]+@[^<>\s]+)>:\s*([^\n]+(?:\n(?!\n)[^\n]+)*)/', $text, $mm, PREG_SET_ORDER)) {

                foreach ($mm as $m) {

                    $email = strtolower(trim($m[1]));

                    $diag = trim((string) preg_replace('/\s+/', ' ', $m[2]));

                    if (preg_match('/\b([45]\.[0-9]{1,3}\.[0-9]{1,3})\b/', $diag, $s)) {

                        $status = $s[1];

                    } elseif (preg_match('/\b([45])[0-9]{2}\b/', $diag, $c)) {

                        $status = $c[1].'.0.0';

                    } else {

                        $status = '';

                    }

                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                        $records[] = [$email, $status !== '' ? 'failed' : '', $status, $diag];

                    }

                }

            }

        }



        return $records;

    }



    private function classify(string $action, string $status, string $diag): string

    {

        if (in_array($action, ['delayed', 'delivered', 'relayed', 'expanded'], true)) {

            return 'ignore';

        }

        if ($status === '') {

            return 'unknown';

        }

        if (str_starts_with($status, '4') || $status === '5.2.2') {

            return 'soft';

        }

        if (preg_match('/^5\.(7|6)\./', $status) === 1 || $status === '5.3.4') {

            return 'policy';

        }

        if (preg_match('/^5\.(5|0)\./', $status) === 1) {

            return preg_match('/user unknown|no such user|nosuchuser|does not exist|doesn.t exist|unknown user|invalid (mailbox|recipient)|mailbox unavailable|recipient (address )?rejected|address rejected|n.existe pas/i', $diag) === 1 ? 'hard' : 'unknown';

        }



        return str_starts_with($status, '5') ? 'hard' : 'unknown';

    }



    private function store(int $uid, string $email, string $kind, string $status, string $diag, ?string $appliedTo = null, ?int $appliedId = null): void

    {

        DB::table('mail_bounces')->insertOrIgnore([

            'mailbox_uid' => $uid,

            'email' => Str::limit($email, 254, ''),

            'kind' => $kind,

            'status_code' => $status !== '' ? Str::limit($status, 12, '') : null,

            'diagnostic' => $diag !== '' ? Str::limit($diag, 250, '') : null,

            'applied_to' => $appliedTo,

            'applied_id' => $appliedId,

            'created_at' => now(),

            'updated_at' => now(),

        ]);

    }



    private function mask(string $email): string

    {

        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');



        return substr($local, 0, 2).'***@'.$domain;

    }

}

