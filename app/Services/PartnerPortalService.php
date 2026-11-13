<?php

namespace App\Services;

use App\Mail\PartnerPortalMessage;
use App\Models\PartnerDocument;
use App\Models\PartnerShare;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PartnerPortalService
{
    public function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function knownEmail(string $email): bool
    {
        $email = $this->normalize($email);

        return DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()
            || DB::table('registrations')->whereNull('anonymized_at')
                ->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists();
    }

    public function event(PartnerShare $share, string $kind, ?string $detail = null, ?int $document = null): void
    {
        DB::table('partner_events')->insert([
            'partner_share_id' => $share->id,
            'generation' => $share->generation,
            'partner_document_id' => $document,
            'kind' => $kind,
            'detail' => $detail,
            'created_at' => now(),
        ]);
    }

    private function payload(array $data): string
    {
        return json_encode([
            '_jnuaf_partner_mail' => 1,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function sendPortalMail(string $recipient, string $subject, array $data): void
    {
        Mail::to($recipient)->send(new PartnerPortalMessage($subject, $data));
    }

    public function notice(string $key, string $recipient, string $subject, string $body, ?PartnerShare $codeShare = null): void
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        DB::table('partner_notices')->insertOrIgnore([
            'unique_key' => hash('sha256', $key.'|'.$recipient),
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => Crypt::encryptString($body),
            'context_share_id' => $codeShare?->id,
            'context_generation' => $codeShare?->generation,
            'context_code_hash' => $codeShare?->code_hash,
            'attempts' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function admins(string $key, string $subject, string $body): void
    {
        foreach (config('partner_portal.admin_emails', []) as $email) {
            $this->notice($key, $email, $subject, $body);
        }
    }

    // Cette méthode doit être appelée avec la ligne du partage verrouillée.
    public function revoke(PartnerShare $share, string $reason): void
    {
        if ($share->status !== 'active') {
            return;
        }

        $share->forceFill([
            'status' => 'revoked',
            'revoked_reason' => $reason,
            'code_hash' => null,
            'code_expires_at' => null,
            'next_code_at' => null,
        ])->save();

        $this->event($share, 'lien_invalidé', $reason);
        $key = 'revoked:'.$share->id.':'.$share->generation;

        // Le motif interne ne figure jamais dans le message partenaire.
        $this->notice(
            $key.':partner',
            $share->email,
            'JNUAF — Renouveler votre accès documentaire',
            $this->payload([
                'kind' => 'partner_revoked',
                'name' => $share->name,
            ])
        );

        $this->admins(
            $key.':admin',
            'JNUAF — Accès partenaire à renouveler',
            $this->payload([
                'kind' => 'admin_revoked',
                'name' => $share->name,
                'email' => $share->email,
                'reason' => $reason,
                'url' => route('admin.partner-portal.show', $share),
            ])
        );
    }

    public function expireLocked(PartnerShare $share): void
    {
        if ($share->status !== 'active') {
            return;
        }

        if ($share->expires_at->isPast()) {
            $this->revoke($share, 'Date de fin du partage atteinte');
        } elseif ($share->code_count >= 3 && $share->code_hash
            && $share->code_expires_at && $share->code_expires_at->isPast()) {
            $this->revoke($share, 'Troisième code expiré sans connexion');
        }
    }

    public function findToken(string $token): PartnerShare
    {
        $share = PartnerShare::where('token_hash', hash('sha256', $token))->first();

        if (! $share) {
            abort(response()->view('partner-portal.unavailable', [], 410, [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Referrer-Policy' => 'no-referrer',
            ]));
        }

        return DB::transaction(function () use ($share) {
            $locked = PartnerShare::lockForUpdate()->findOrFail($share->id);
            if (! hash_equals((string) $share->token_hash, (string) $locked->token_hash)) {
                abort(410);
            }
            $this->expireLocked($locked);
            return $locked;
        });
    }

    public function sendInvitation(PartnerShare $share): void
    {
        $admins = config('partner_portal.admin_emails', []);

        if (! $admins
            || count(array_filter($admins, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))) !== count($admins)
            || ! filter_var(config('partner_portal.contact_email'), FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'configuration' => 'Configurez les véritables adresses PARTNER_ADMIN_EMAILS et PARTNER_CONTACT_EMAIL avant l’envoi.',
            ]);
        }

        try {
            $this->root();
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['configuration' => $exception->getMessage()]);
        }

        if (! $this->knownEmail($share->email)) {
            throw ValidationException::withMessages([
                'email' => 'Cette adresse n’est plus présente dans les comptes ou inscriptions non anonymisées.',
            ]);
        }

        if ($share->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'expires_at' => 'Prolongez la date de fin avant de renouveler le lien.',
            ]);
        }

        DB::transaction(function () use ($share) {
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            $token = bin2hex(random_bytes(32));

            $share->forceFill([
                'token_hash' => hash('sha256', $token),
                'generation' => $share->generation + 1,
                'status' => 'active',
                'revoked_reason' => null,
                'code_count' => 0,
                'failed_attempts' => 0,
                'code_hash' => null,
                'code_expires_at' => null,
                'next_code_at' => null,
                'first_opened_at' => null,
                'last_login_at' => null,
                'invited_at' => now(),
            ])->save();

            $this->sendPortalMail(
                $share->email,
                'JNUAF — Votre espace documentaire partenaire',
                [
                    'kind' => 'invitation',
                    'name' => $share->name,
                    'email' => $share->email,
                    'url' => route('partner-portal.landing', $token),
                ]
            );

            $this->event($share, 'invitation_transmise', 'Message accepté par le transport email ; la livraison en boîte n’est pas garantie.');
        });
    }

    private function digest(PartnerShare $share, string $code): string
    {
        return hash_hmac(
            'sha256',
            $share->id.'|'.$share->generation.'|'.$share->email.'|'.$code,
            (string) config('app.key')
        );
    }

    public function requestCode(PartnerShare $share, string $email, string $ip): void
    {
        $email = $this->normalize($email);
        $key = 'partner-code:'.hash('sha256', $email);
        $ipKey = 'partner-code-ip:'.hash('sha256', $ip);

        if (RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            return;
        }

        RateLimiter::hit($key, 3600);
        RateLimiter::hit($ipKey, 3600);

        if (! hash_equals($share->email, $email) || ! $this->knownEmail($email)) {
            return;
        }

        DB::transaction(function () use ($share) {
            $snapshotHash = $share->token_hash;
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            if (! hash_equals((string) $snapshotHash, (string) $share->token_hash)) {
                return;
            }
            $this->expireLocked($share);

            if ($share->status !== 'active') {
                return;
            }
            if ($share->code_hash && ! $share->code_expires_at) {
                return;
            }
            if ($share->code_count >= 3) {
                if (! $share->code_hash) {
                    $this->revoke($share, 'Trois codes déjà utilisés ; nouveau lien nécessaire');
                }
                return;
            }
            if ($share->next_code_at && now()->lt($share->next_code_at)) {
                return;
            }

            $code = (string) random_int(100000, 999999);
            $count = $share->code_count + 1;
            $share->forceFill([
                'code_count' => $count,
                'code_hash' => $this->digest($share, $code),
                'code_expires_at' => null,
                'next_code_at' => null,
            ])->save();

            $this->notice(
                'code:'.$share->id.':'.$share->generation.':'.$count,
                $share->email,
                'JNUAF — Votre code de connexion',
                $this->payload(['kind' => 'code', 'name' => $share->name, 'code' => $code]),
                $share
            );
            $this->event($share, 'code_demandé', 'Demande numéro '.$count.' ; envoi en attente.');
        });
    }

    public function verifyCode(PartnerShare $share, string $email, string $code): bool
    {
        return DB::transaction(function () use ($share, $email, $code) {
            $snapshotHash = $share->token_hash;
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            if (! hash_equals((string) $snapshotHash, (string) $share->token_hash)) {
                return false;
            }
            $this->expireLocked($share);

            if ($share->status !== 'active'
                || ! hash_equals($share->email, $this->normalize($email))
                || ! $share->code_hash || ! $share->code_expires_at
                || $share->code_expires_at->isPast()) {
                return false;
            }

            if (! hash_equals($share->code_hash, $this->digest($share, $code))) {
                $share->failed_attempts++;
                $share->save();
                $this->event($share, 'code_incorrect', 'Erreur '.$share->failed_attempts.' sur 3');
                if ($share->failed_attempts >= 3) {
                    $this->revoke($share, 'Trois codes saisis incorrectement');
                }
                return false;
            }

            if (! $this->knownEmail($share->email)) {
                $this->revoke($share, 'Adresse retirée des comptes et inscriptions autorisés');
                return false;
            }

            $share->forceFill([
                'code_hash' => null,
                'code_expires_at' => null,
                'last_login_at' => now(),
            ])->save();
            $this->event($share, 'connexion_réussie');
            return true;
        });
    }

   public function root(): string
{
    $privateSetting = trim(
        (string) config('partner_portal.private_root')
    );

    $webSetting = trim(
        (string) config('partner_portal.web_root')
    );

    if ($privateSetting === '' || $webSetting === '') {
        throw new RuntimeException(
            'Configurez PARTNER_PRIVATE_ROOT et PARTNER_WEB_ROOT.'
        );
    }

    $root = realpath($privateSetting);
    $web = realpath($webSetting);
    $public = realpath(public_path());

    if (
        $root === false
        || $web === false
        || $public === false
        || ! is_dir($root)
        || ! is_dir($web)
        || ! is_dir($public)
    ) {
        throw new RuntimeException(
            'Les dossiers privé et publics doivent exister '
            .'et être accessibles à PHP.'
        );
    }

    // Ces deux protections restent actives, y compris en console.
    $publicRoots = [
        $web,
        $public,
    ];

    // DOCUMENT_ROOT n'est exploité que pour une requête web,
    // et jamais lorsqu'il est vide.
    if (! app()->runningInConsole()) {
        $documentRootSetting = trim(
            (string) request()->server('DOCUMENT_ROOT', '')
        );

        if ($documentRootSetting !== '') {
            $documentRoot = realpath($documentRootSetting);

            if ($documentRoot !== false && is_dir($documentRoot)) {
                $publicRoots[] = $documentRoot;
            }
        }
    }

    foreach (array_unique($publicRoots) as $publicRoot) {
        $prefix = rtrim(
            $publicRoot,
            DIRECTORY_SEPARATOR
        ).DIRECTORY_SEPARATOR;

        if (
            $root === $publicRoot
            || str_starts_with($root, $prefix)
        ) {
            throw new RuntimeException(
                'Stockage refusé : le dossier des PDF '
                .'est à l’intérieur de la racine web.'
            );
        }
    }

    if (! is_readable($root) || ! is_writable($root)) {
        throw new RuntimeException(
            'Le dossier privé doit être lisible '
            .'et inscriptible par PHP.'
        );
    }

    return $root;
}

    public function path(PartnerDocument $document): string
    {
        $root = $this->root();
        if (! preg_match('/\A[a-f0-9]{48}\.pdf\z/', $document->stored_name)) {
            abort(404);
        }
        $path = realpath($root.DIRECTORY_SEPARATOR.$document->stored_name);
        abort_unless(
            $path && is_file($path)
            && ! is_link($root.DIRECTORY_SEPARATOR.$document->stored_name)
            && str_starts_with($path, $root.DIRECTORY_SEPARATOR),
            404
        );
        return $path;
    }

    public function accessEvent(PartnerShare $share, PartnerDocument $document, string $kind): void
    {
        DB::transaction(function () use ($share, $document, $kind) {
            $share = PartnerShare::lockForUpdate()->findOrFail($share->id);
            $first = ! DB::table('partner_events')
                ->where('partner_share_id', $share->id)
                ->where('partner_document_id', $document->id)
                ->where('kind', 'premier_accès_document')->exists();

            $this->event($share, $kind, 'Demande autorisée ; ne prouve pas une lecture complète.', $document->id);

            if ($first) {
                $this->event($share, 'premier_accès_document', null, $document->id);
                $action = match ($kind) {
                    'lecture_demandée' => 'Consultation',
                    'téléchargement_demandé' => 'Téléchargement',
                    default => 'Accès au document',
                };

                $this->admins(
                    'document:'.$share->id.':'.$document->id,
                    'JNUAF — Premier accès à un document partenaire',
                    $this->payload([
                        'kind' => 'admin_document',
                        'name' => $share->name,
                        'email' => $share->email,
                        'document' => $document->title,
                        'action' => $action,
                        'date' => now()->timezone(config('app.timezone'))->format('d/m/Y à H:i T'),
                        'url' => route('admin.partner-portal.show', $share),
                    ])
                );
            }
        });
    }

    private function structuredNotice(string $plain): ?array
    {
        $decoded = json_decode($plain, true);
        if (is_array($decoded)
            && ($decoded['_jnuaf_partner_mail'] ?? null) === 1
            && is_array($decoded['data'] ?? null)) {
            return $decoded['data'];
        }
        return null;
    }

    private function notificationData(object $notice): array
    {
        $plain = Crypt::decryptString($notice->body);
        $data = $this->structuredNotice($plain);
        if ($data !== null) {
            $allowed = ['partner_revoked', 'admin_revoked', 'admin_document', 'generic_admin', 'generic_partner'];
            if (! in_array($data['kind'] ?? '', $allowed, true)) {
                throw new RuntimeException('Type de notification documentaire non reconnu.');
            }
            return $data;
        }

        // Compatibilité avec les notifications texte déjà présentes en base.
        if (str_contains($notice->subject, 'Votre lien documentaire')
            || str_contains($notice->subject, 'Renouveler votre accès')) {
            return ['kind' => 'partner_revoked', 'name' => ''];
        }

        $legacyAdmin = str_contains($notice->subject, 'Accès partenaire à renouveler')
            || str_contains($notice->subject, 'Premier accès à un document partenaire');
        $adminRecipients = array_map(fn ($email) => $this->normalize($email), config('partner_portal.admin_emails', []));
        if ($legacyAdmin && in_array($this->normalize($notice->recipient), $adminRecipients, true)) {
            return [
                'kind' => 'generic_admin',
                'url' => route('admin.partner-portal.index'),
            ];
        }

        // Ne jamais afficher un ancien corps inconnu : il peut contenir des détails internes.
        return ['kind' => 'generic_partner'];
    }

    private function dispatchCode(object $notice): void
    {
        DB::transaction(function () use ($notice) {
            $share = PartnerShare::lockForUpdate()->find($notice->context_share_id);
            if (! $share) {
                return;
            }
            $this->expireLocked($share);
            if ($share->status !== 'active'
                || (int) $share->generation !== (int) $notice->context_generation
                || ! $share->code_hash
                || ! hash_equals($share->code_hash, $notice->context_code_hash)) {
                return;
            }
            if (! $this->knownEmail($share->email)) {
                $this->revoke($share, 'Adresse retirée des comptes et inscriptions autorisés');
                return;
            }
            if ($share->code_expires_at) {
                return;
            }

            $plain = Crypt::decryptString($notice->body);
            $data = $this->structuredNotice($plain);
            $code = '';
            if (($data['kind'] ?? null) === 'code') {
                $code = (string) ($data['code'] ?? '');
            } elseif ($data === null && preg_match('/Votre code JNUAF\s*:\s*([0-9]{6})\b/u', $plain, $matches)) {
                $code = $matches[1];
            }
            if (! preg_match('/\A[0-9]{6}\z/', $code)
                || ! hash_equals($share->code_hash, $this->digest($share, $code))) {
                throw new RuntimeException('Contenu du code documentaire non reconnu ou incohérent.');
            }

            $expiry = now()->addSeconds(config('partner_portal.code_seconds'));
            $delay = $share->code_count === 1
                ? config('partner_portal.second_delay')
                : config('partner_portal.third_delay');
            $share->forceFill([
                'code_expires_at' => $expiry,
                'next_code_at' => $share->code_count < 3 ? $expiry->copy()->addSeconds($delay) : null,
            ])->save();

            $this->sendPortalMail($notice->recipient, $notice->subject, [
                'kind' => 'code',
                'name' => $share->name,
                'code' => $code,
                'valid_minutes' => (int) ceil(config('partner_portal.code_seconds') / 60),
            ]);
            $this->event($share, 'code_transmis', 'Code numéro '.$share->code_count.' ; accepté par le transport email.');
        });
    }

    public function processMaintenance(): void
    {
        PartnerShare::where('status', 'active')->select('id')->chunkById(100, function ($shares) {
            foreach ($shares as $share) {
                DB::transaction(function () use ($share) {
                    $row = PartnerShare::lockForUpdate()->find($share->id);
                    if ($row) {
                        $this->expireLocked($row);
                    }
                });
            }
        });

        $ids = DB::table('partner_notices')->whereNull('sent_at')
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->where(fn ($query) => $query->whereNull('claimed_at')->orWhere('claimed_at', '<', now()->subMinutes(10)))
            ->orderBy('id')->limit(25)->pluck('id');

        foreach ($ids as $id) {
            $notice = DB::transaction(function () use ($id) {
                $row = DB::table('partner_notices')->lockForUpdate()->find($id);
                if (! $row || $row->sent_at
                    || ($row->next_attempt_at && now()->lt($row->next_attempt_at))
                    || ($row->claimed_at && now()->diffInSeconds($row->claimed_at, true) < 600)) {
                    return null;
                }
                DB::table('partner_notices')->where('id', $id)->update([
                    'claimed_at' => now(),
                    'attempts' => $row->attempts + 1,
                    'updated_at' => now(),
                ]);
                return $row;
            });

            if (! $notice) {
                continue;
            }

            try {
                if ($notice->context_share_id) {
                    $this->dispatchCode($notice);
                } else {
                    $this->sendPortalMail(
                        $notice->recipient,
                        $notice->subject,
                        $this->notificationData($notice)
                    );
                }
                DB::table('partner_notices')->where('id', $id)->update([
                    'sent_at' => now(),
                    'claimed_at' => null,
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $exception) {
                report($exception);
                DB::table('partner_notices')->where('id', $id)->update([
                    'claimed_at' => null,
                    'next_attempt_at' => now()->addMinutes(5),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
