<?php
namespace App\Services;

use App\Mail\NewsletterMessage;
use App\Models\NewsletterAttachment;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterDelivery;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class NewsletterService
{
    public const SOURCES = ['manual' => 'Liste ajoutée manuellement', 'users' => 'Comptes du site', 'registrations' => 'Inscriptions'];

    public function normalize(string $email): string { return mb_strtolower(trim($email)); }

    public function saveContact(string $email, ?string $name, string $source, bool $subscribe = false, ?string $proof = null): NewsletterSubscriber
    {
        $email = $this->normalize($email);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => 'Adresse email invalide.']);
        }
        if ($subscribe && trim((string) $proof) === '') {
            throw ValidationException::withMessages(['consent_note' => 'Indiquez la preuve ou le contexte autorisant cet abonnement.']);
        }
        return DB::transaction(function () use ($email, $name, $source, $subscribe, $proof) {
            $contact = NewsletterSubscriber::where('email', $email)->lockForUpdate()->first();
            if (! $contact) {
                $contact = new NewsletterSubscriber(['email' => $email, 'name' => $name, 'status' => 'pending', 'sources' => []]);
            }
            $contact->sources = array_values(array_unique(array_merge($contact->sources ?? [], [$source])));
            if (! $contact->name && $name) { $contact->name = $name; }
            if ($subscribe) {
                $contact->status = 'subscribed';
                $contact->consent_note = $proof;
                $contact->consented_at = now();
                $contact->unsubscribed_at = null;
                $contact->bounced_at = null;
                $contact->bounce_reason = null;
            }
            $contact->save();
            return $contact;
        }, 3);
    }

    public function importContacts(array $sources): int
    {
        $count = 0;
        foreach (array_intersect($sources, ['users', 'registrations']) as $source) {
            $query = DB::table($source)->select($source === 'users' ? ['id', 'email', 'name'] : ['id', 'email', 'first_name', 'last_name']);
            if ($source === 'registrations') { $query->whereNull('anonymized_at'); }
            $query->orderBy('id')->chunkById(100, function ($rows) use ($source, &$count) {
                foreach ($rows as $row) {
                    if (! filter_var($this->normalize((string) $row->email), FILTER_VALIDATE_EMAIL)) { continue; }
                    $name = $source === 'users' ? $row->name : trim($row->first_name.' '.$row->last_name);
                    // Importer n'abonne jamais et ne réactive jamais une opposition.
                    $this->saveContact($row->email, $name, $source);
                    $count++;
                }
            });
        }
        return $count;
    }

    public function eligible(NewsletterCampaign $campaign)
    {
        return NewsletterSubscriber::where('status', 'subscribed')->where(function ($query) use ($campaign) {
            foreach ($campaign->sources as $source) { $query->orWhereJsonContains('sources', $source); }
        });
    }

    public function root(): string
    {
        $base = app(PartnerPortalService::class)->root();
        $path = $base.DIRECTORY_SEPARATOR.'newsletters';
        if (is_link($path)) { throw new RuntimeException('Le dossier newsletters ne doit pas être un lien symbolique.'); }
        if (! is_dir($path) && ! mkdir($path, 0700) && ! is_dir($path)) {
            throw new RuntimeException('Impossible de créer le stockage privé des newsletters.');
        }
        $real = realpath($path);
        if (! $real || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR) || ! is_writable($real)) {
            throw new RuntimeException('Stockage newsletter privé inaccessible.');
        }
        return $real;
    }

    public function attachmentPath(NewsletterAttachment $file): string
    {
        $root = $this->root();
        if (! preg_match('/\A[a-f0-9]{40}\.(pdf|png|jpg|jpeg|docx|xlsx|pptx)\z/', $file->stored_name)) {
            throw new RuntimeException('Nom de pièce jointe non reconnu.');
        }
        $path = $root.DIRECTORY_SEPARATOR.$file->stored_name;
        $real = realpath($path);
        if (! $real || is_link($path) || ! is_file($real) || ! str_starts_with($real, $root.DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Pièce jointe absente ou inaccessible.');
        }
        return $real;
    }

    public function addAttachments(NewsletterCampaign $campaign, array $uploads): void
    {
        if (! $uploads) { return; }
        $root = $this->root();
        $created = [];
        try {
            DB::transaction(function () use ($campaign, $uploads, $root, &$created) {
                $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
                if ($campaign->status !== 'draft') { throw ValidationException::withMessages(['files' => 'Cette campagne n’est plus modifiable.']); }
                $existingCount = $campaign->attachments()->count();
                $bytes = (int) $campaign->attachments()->sum('bytes');
                if ($existingCount + count($uploads) > config('newsletter.max_attachments')) {
                    throw ValidationException::withMessages(['files' => 'Cinq pièces jointes maximum par campagne.']);
                }
                foreach ($uploads as $upload) {
                    $bytes += $upload->getSize();
                    if ($bytes > config('newsletter.max_total_bytes')) {
                        throw ValidationException::withMessages(['files' => 'Les pièces jointes dépassent 10 Mo au total.']);
                    }
                    $ext = strtolower($upload->getClientOriginalExtension());
                    if (! in_array($ext, ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'pptx'], true)) {
                        throw ValidationException::withMessages(['files' => 'Extension non autorisée.']);
                    }
                    $mime = $upload->getMimeType() ?: 'application/octet-stream';
                    $size = $upload->getSize();
                    $stored = bin2hex(random_bytes(20)).'.'.$ext;
                    $baseName = Str::slug(pathinfo(basename(str_replace('\\', '/', $upload->getClientOriginalName())), PATHINFO_FILENAME)) ?: 'document';
                    $upload->move($root, $stored);
                    $created[] = $root.DIRECTORY_SEPARATOR.$stored;
                    @chmod(end($created), 0600);
                    $campaign->attachments()->create([
                        'stored_name' => $stored, 'download_name' => substr($baseName, 0, 150).'.'.$ext,
                        'mime' => $mime, 'bytes' => $size,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($created as $path) { if (is_file($path)) { @unlink($path); } }
            throw $exception;
        }
    }

    public function smtp(): string
{
    $settings = config('mail.mailers.newsletter');

    if (
        ! is_array($settings)
        || ($settings['transport'] ?? null) !== 'smtp'
    ) {
        throw new RuntimeException(
            'La connexion SMTP newsletter doit être configurée.'
        );
    }

    foreach (['host', 'username', 'password'] as $key) {
        if (trim((string) ($settings[$key] ?? '')) === '') {
            throw new RuntimeException(
                'La configuration SMTP newsletter est incomplète.'
            );
        }
    }

    $settings['timeout'] = max(
        1,
        (int) config('newsletter.smtp_timeout', 20)
    );

    config([
        'mail.mailers.jnuaf_newsletter' => $settings,
    ]);

    return 'jnuaf_newsletter';
}

    public function sendTest(NewsletterCampaign $campaign, string $email): void
    {
        Mail::mailer($this->smtp())->to($email)->send(new NewsletterMessage($campaign->load('attachments')));
    }

    public function queueCampaign(NewsletterCampaign $campaign): int
    {
        $this->smtp();
        foreach ($campaign->attachments as $file) { $this->attachmentPath($file); }
        return DB::transaction(function () use ($campaign) {
            $campaign = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            if ($campaign->status !== 'draft') { throw ValidationException::withMessages(['campaign' => 'Cette campagne a déjà été mise en envoi.']); }
            $count = $this->eligible($campaign)->count();
            if ($count < 1) { throw ValidationException::withMessages(['campaign' => 'Aucun abonné actif ne correspond aux sources choisies.']); }
            if ($count > config('newsletter.campaign_limit')) {
                throw ValidationException::withMessages(['campaign' => 'Plus de 5 000 destinataires : segmenter la campagne avant envoi.']);
            }
            $this->eligible($campaign)->orderBy('id')->chunkById(100, function ($contacts) use ($campaign) {
                $rows = [];
                foreach ($contacts as $contact) {
                    $token = bin2hex(random_bytes(32));
                    $rows[] = [
                        'newsletter_campaign_id' => $campaign->id, 'newsletter_id' => $contact->id,
                        'email' => $contact->email, 'token_hash' => hash('sha256', $token),
                        'token_ciphertext' => Crypt::encryptString($token), 'status' => 'pending', 'attempts' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ];
                }
                DB::table('newsletter_deliveries')->insert($rows);
            });
            $campaign->update([
                'status' => 'queued', 'queued_at' => now(),
                'web_expires_at' => now()->addDays(config('newsletter.web_days')),
            ]);
            return $count;
        });
    }

    public function token(NewsletterDelivery $delivery): string
    {
        return Crypt::decryptString($delivery->token_ciphertext);
    }

    public function delivery(string $token): NewsletterDelivery
    {
        abort_unless(preg_match('/\A[a-f0-9]{64}\z/', $token), 404);
        return NewsletterDelivery::with(['campaign.attachments', 'subscriber'])
            ->where('token_hash', hash('sha256', $token))->firstOrFail();
    }

    public function unsubscribe(NewsletterDelivery $delivery): void
    {
        DB::transaction(function () use ($delivery) {
            $contact = NewsletterSubscriber::lockForUpdate()->findOrFail($delivery->newsletter_id);
            $contact->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);
            // L'opposition concerne toutes les campagnes, quelle que soit la source.
            NewsletterDelivery::where('newsletter_id', $contact->id)->where('status', 'pending')
                ->update(['status' => 'skipped', 'error' => 'Désinscription newsletter', 'updated_at' => now()]);
        });
    }

    
public function displayTimezone(): string

    {

        return (string) config('jnuaf_newsletter.timezone', 'Europe/Paris');

    }



    public function parseLocal(string $value): \Illuminate\Support\Carbon

    {

        return \Illuminate\Support\Carbon::createFromFormat('Y-m-d\TH:i', $value, $this->displayTimezone())->setTimezone('UTC');

    }



    public function dispatchDue(): int

    {

        $started = 0;

        $due = NewsletterCampaign::where('status', 'scheduled')

            ->where('scheduled_at', '<=', now())

            ->orderBy('scheduled_at')

            ->limit(3)

            ->get();



        foreach ($due as $campaign) {

            $claimed = NewsletterCampaign::whereKey($campaign->id)->where('status', 'scheduled')->update(['status' => 'draft']);

            if ($claimed !== 1) { continue; }

            try {

                $this->queueCampaign($campaign->fresh());

                $started++;

            } catch (\Throwable $exception) {

                report($exception);

                NewsletterCampaign::whereKey($campaign->id)->where('status', 'draft')->update(['scheduled_at' => null]);

            }

        }



        return $started;

    }




    public function reportCampaign(NewsletterCampaign $campaign): void

    {

        try {

            $to = config('jnuaf_audit.account_audit_email');

            if (! $to) {

                return;

            }

            $by = $campaign->deliveries()->selectRaw('status, COUNT(*) AS c')->groupBy('status')->pluck('c', 'status');

            $sent = (int) ($by['sent'] ?? 0);

            $failed = (int) ($by['failed'] ?? 0);

            $skipped = (int) ($by['skipped'] ?? 0);

            $uncertain = (int) ($by['uncertain'] ?? 0);

            $total = (int) $by->sum();

            $attempted = $sent + $failed;

            $rate = $attempted > 0 ? round($failed * 100 / $attempted, 1) : 0;

            $since = $campaign->queued_at ?? $campaign->created_at;

            $unsub = NewsletterSubscriber::where('status', 'unsubscribed')->where('unsubscribed_at', '>=', $since)->count();

            $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->timezone('Europe/Paris')->format('d/m/Y H:i') : '-';

            $name = (string) ($campaign->subject ?? $campaign->title ?? $campaign->name ?? ('#'.$campaign->id));

            $lines = [

                "Campagne : $name",

                "D\u{e9}but : ".$fmt($campaign->queued_at),

                "Fin : ".$fmt($campaign->finished_at ?? now()),

                '',

                "Destinataires : $total",

                "Envoy\u{e9}s : $sent",

                "\u{c9}checs : $failed ($rate % des tentatives)",

                "Ignor\u{e9}s : $skipped",

                "Incertains (\u{e0} v\u{e9}rifier) : $uncertain",

                "D\u{e9}sinscriptions pendant l'envoi : $unsub",

            ];

            \Illuminate\Support\Facades\Mail::raw(implode("\n", $lines), function ($m) use ($to, $name) {

                $m->to($to)->subject('Newsletter termin'."\u{e9}e : ".$name);

            });

        } catch (\Throwable $e) {

            report($e);

        }

    }




    public function finishCampaigns(): void

    {
        NewsletterCampaign::whereIn('status', ['queued', 'sending'])->each(function ($campaign) {
            if (! $campaign->deliveries()->whereIn('status', ['pending', 'processing'])->exists()) {
                $campaign->update(['status' => 'completed', 'finished_at' => now()]);
                $this->reportCampaign($campaign);
            }
        });
    }

    
private const BREAKER_RATE = 5.0;



    private const BREAKER_MIN_SENT = 100;



    private function breakerTripped(): bool

    {

        if (Cache::has('jnuaf-newsletter-breaker-ignore')) { return false; }

        if (Cache::has('jnuaf-newsletter-breaker')) { return true; }

        try {

            foreach (NewsletterCampaign::where('status', 'sending')->get() as $c) {

                $sent = $c->deliveries()->where('status', 'sent')->count();

                if ($sent < self::BREAKER_MIN_SENT) { continue; }

                $since = $c->queued_at ?? $c->created_at;

                $bounced = DB::table('mail_bounces as b')

                    ->join('newsletter_deliveries as d', 'd.email', '=', 'b.email')

                    ->where('d.newsletter_campaign_id', $c->id)->where('d.status', 'sent')

                    ->whereIn('b.kind', ['hard', 'policy'])->where('b.created_at', '>=', $since)

                    ->distinct()->count('b.email');

                $rate = round($bounced * 100 / $sent, 1);

                if ($rate >= self::BREAKER_RATE) {

                    Cache::forever('jnuaf-newsletter-breaker', now()->toDateTimeString().' campagne '.$c->id.' : '.$rate.' %');

                    try {

                        app(\App\Services\AdminNotifier::class)->send(new \App\Mail\AdminAlert(

                            title: "Newsletter suspendue : trop de rebonds",

                            intro: "L'envoi a \u{e9}t\u{e9} arr\u{ea}t\u{e9} automatiquement pour prot\u{e9}ger la r\u{e9}putation du domaine. V\u{e9}rifiez la liste avant toute reprise.",

                            details: ["Campagne" => (string) $c->id, "Taux de rebonds" => $rate.' %', "Envois" => (string) $sent, "Rebonds" => (string) $bounced],

                            url: route('admin.newsletter.show', $c),

                            action: "Voir la campagne",

                        ));

                    } catch (\Throwable $e) { report($e); }

                    return true;

                }

            }

        } catch (\Throwable $e) { report($e); }

        return false;

    }



    
public function processBatch(): int
    {
        $lock = Cache::lock('jnuaf-newsletter-batch', 120);
        if (! $lock->get()) { return 0; }
        $sent = 0;
        try {
            $started = microtime(true);
            if ($this->breakerTripped()) { return 0; }
            NewsletterDelivery::where('status', 'processing')->where('processing_at', '<', now()->subMinutes(15))
                ->update(['status' => 'uncertain', 'error' => 'Traitement interrompu : vérifier avant de renvoyer.', 'updated_at' => now()]);
            while (microtime(true) - $started < config('newsletter.batch_seconds')) {
                if (Cache::has('jnuaf-newsletter-cooldown')) { break; }
                $forced = (int) Cache::get('jnuaf-newsletter-force', 0) > 0;
                if (! $forced && (RateLimiter::tooManyAttempts('jnuaf-newsletter-minute', max(1, config('newsletter.per_minute')))
                    || RateLimiter::tooManyAttempts('jnuaf-newsletter-hour', max(1, config('newsletter.per_hour')))
                    || RateLimiter::tooManyAttempts('jnuaf-newsletter-day', max(1, config('newsletter.per_day'))))) { break; }
                $delivery = DB::transaction(function () {
                    $row = NewsletterDelivery::where('status', 'pending')
                        ->whereHas('campaign', fn ($q) => $q->whereIn('status', ['queued', 'sending']))
                        ->orderByRaw('prioritized_at is null')->orderByDesc('prioritized_at')->orderBy('id')->lockForUpdate()->first();
                    if (! $row) { return null; }
                    $row->update(['status' => 'processing', 'processing_at' => now(), 'attempts' => $row->attempts + 1]);
                    return $row;
                });
                if (! $delivery) { break; }
                $delivery->load(['subscriber', 'campaign.attachments']);
                if ($delivery->subscriber->status !== 'subscribed'
                    || ! in_array($delivery->campaign->status, ['queued', 'sending'], true)) {
                    $delivery->update(['status' => 'skipped', 'error' => 'Abonnement inactif ou campagne arrêtée.']);
                    continue;
                }
                if (! filter_var($delivery->email, FILTER_VALIDATE_EMAIL)) {
                    $delivery->update(['status' => 'skipped', 'error' => 'Adresse invalide (format) : non envoyee.']);
                    continue;
                }
                if ($delivery->campaign->web_expires_at?->isPast()) {
                    $delivery->update(['status' => 'skipped', 'error' => 'Campagne trop ancienne : dupliquer son contenu dans un nouveau brouillon.']);
                    continue;
                }
                RateLimiter::hit('jnuaf-newsletter-minute', 60);
                RateLimiter::hit('jnuaf-newsletter-hour', 3600);
                RateLimiter::hit('jnuaf-newsletter-day', 86400);
                if ($forced) { Cache::decrement('jnuaf-newsletter-force'); }
                NewsletterCampaign::whereKey($delivery->newsletter_campaign_id)->where('status', 'queued')->update(['status' => 'sending']);
                try {
                    Mail::mailer($this->smtp())->to($delivery->email)
                        ->send(new NewsletterMessage($delivery->campaign, $delivery));
                    $delivery->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
                    $sent++;
                } catch (\Throwable $exception) {
                    report($exception);
                    if (preg_match('/L-(RATE|IPSPAM|SIZE)|rate limit|too many|\b421\b/i', $exception->getMessage())) {
                        $delivery->update(['status' => 'pending', 'attempts' => max(0, $delivery->attempts - 1), 'error' => 'Limite du fournisseur atteinte : remis en file.']);
                        Cache::forget('jnuaf-newsletter-force');
                        Cache::put('jnuaf-newsletter-cooldown', now()->toDateTimeString(), now()->addMinutes(60));
                        break;
                    }
                    $delivery->update(['status' => 'failed', 'error' => 'Échec d’envoi : '.\Illuminate\Support\Str::limit(trim((string) preg_replace('/\s+/', ' ', class_basename($exception).' - '.$exception->getMessage())), 200).' (vérifier avant une relance)']);
                }
            }
            $this->finishCampaigns();
        } finally { $lock->release(); }
        return $sent;
    }
}
