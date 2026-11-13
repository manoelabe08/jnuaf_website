<?php



namespace App\Services;



use App\Mail\AccountAudit;

use App\Models\SecurityEvent;

use App\Models\SensitiveAction;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\RateLimiter;



class SensitiveActionService

{

    public const WINDOW_SECONDS = 300;

    public const APPROVAL_HOURS = 24;

    private const MAX_ATTEMPTS = 5;

    private const LOCK_SECONDS = 900;



    public function label(string $key): string

    {

        return match ($key) {

            'export_event' => "Export des inscrits d'un \u{e9}v\u{e9}nement",

            'send_attestations' => 'Envoi des attestations de participation',


            'delete_registration' => "Suppression d\u{e9}finitive d'une inscription",

            'delete_territory' => "Suppression d'un territoire",

            'delete_user' => "Suppression d'un compte",

            'create_user' => "Cr\u{e9}ation d'un compte",

            'update_user' => "Modification d'un compte",

            'update_settings' => "Modification des param\u{e8}tres du site",



            'export_subscribers' => "Export des adresses de la newsletter",

            'force_newsletter' => "For\u{e7}age de l'envoi de la newsletter",


            'confirm_delete_user' => "Suppression d\u{e9}finitive d'un compte (compte suspendu en attendant)",

            'delete_event' => "Suppression d'un \u{e9}v\u{e9}nement",

            'delete_committee_application' => "Suppression d'une candidature au comit\u{e9}",

            'delete_partner_inquiry' => "Suppression d'une demande de partenaire",


            'restore_trash' => "Restauration depuis la corbeille",

            'purge_trash' => "Suppression d\u{e9}finitive depuis la corbeille",


            'reactivate_user' => "R\u{e9}activation d'un compte suspendu",

            

'invite_to_apply' => "Envoi d'invitations a candidater au comite",

            'create_committee_account' =>
 "Creation d'un compte pour un membre du comite",

            'toggle_public_page' =>
 "Publication ou masquage d'une page publique",
            'switch_home_page' => "Changement de la page d'accueil",
            default => $key,

        };

    }



    public function fingerprint(Request $request, string $key): string

    {

        $params = collect($request->route()?->parameters() ?? [])

            ->map(fn ($v) => is_object($v) && method_exists($v, 'getKey') ? (string) $v->getKey() : (is_scalar($v) ? (string) $v : ''))

            ->all();

        ksort($params);



        return hash('sha256', $request->user()->id.'|'.$key.'|'.$request->method().'|'.($request->route()?->getName() ?? '').'|'.json_encode($params));

    }



    public function recentlyVerified(Request $request): bool

    {

        $at = (int) $request->session()->get('sensitive.verified_at', 0);



        return $at > 0 && (time() - $at) <= self::WINDOW_SECONDS;

    }



    public function takeFresh(Request $request, string $fingerprint): bool

    {

        $name = 'sensitive.fresh.'.$fingerprint;

        $at = (int) $request->session()->get($name, 0);

        $request->session()->forget($name);



        return $at > 0 && (time() - $at) <= self::WINDOW_SECONDS;

    }



    /** @return string ok|invalid|locked|no_totp */

    public function checkCode(Request $request, User $user, string $code): string

    {

        if (! $user->hasTotp()) {

            return 'no_totp';

        }



        $key = 'sensitive-code:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            return 'locked';

        }



        if (app(TotpService::class)->verify($user, $code)) {

            RateLimiter::clear($key);



            return 'ok';

        }



        RateLimiter::hit($key, self::LOCK_SECONDS);

        $this->log($request, $user, 'Code de confirmation refus'."\u{e9}");



        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {

            $this->notify($user, "Verrouillage : codes de confirmation erron\u{e9}s", ['Essais'.' '."\u{e9}chou\u{e9}s" => (string) self::MAX_ATTEMPTS], $request);



            return 'locked';

        }



        return 'invalid';

    }



    public function pendingTicket(User $user, string $fingerprint): ?SensitiveAction

    {

        return SensitiveAction::where('user_id', $user->id)->where('fingerprint', $fingerprint)

            ->where('status', 'pending')->where('expires_at', '>', now())->first();

    }



    public function approvedTicket(User $user, string $fingerprint): ?SensitiveAction

    {

        return SensitiveAction::where('user_id', $user->id)->where('fingerprint', $fingerprint)

            ->where('status', 'approved')->where('expires_at', '>', now())->orderBy('id')->first();

    }



    public function requestApproval(Request $request, User $user, string $key, string $fingerprint, string $targetUrl, string $method): SensitiveAction

    {

        $existing = $this->pendingTicket($user, $fingerprint);

        if ($existing) {

            return $existing;

        }



        $action = SensitiveAction::create([

            'user_id' => $user->id,

            'action_key' => $key,

            'method' => $method,

            'route_name' => $request->route()?->getName(),

            'fingerprint' => $fingerprint,

            'target_url' => $targetUrl,

            'status' => 'pending',

            'expires_at' => now()->addHours(self::APPROVAL_HOURS),

            'ip' => $request->ip(),

        ]);



        $this->notify($user, "Validation requise du Bureau de Pilotage", ['Action' => $this->label($key)], $request);



        return $action;

    }



    public function log(Request $request, User $user, string $action, array $details = []): void

    {

        SecurityEvent::create([

            'user_id' => $user->id,

            'action' => $action,

            'details' => $details,

            'ip' => $request->ip(),

            'user_agent' => substr((string) $request->userAgent(), 0, 500),

        ]);

    }



    public function notify(User $user, string $title, array $details, ?Request $request = null): void

    {

        $to = config('jnuaf.notify_email') ?: config('jnuaf_audit.account_audit_email');

        if (! $to) {

            return;

        }



        try {

            Mail::to($to)->send(new AccountAudit($title, array_merge(['Compte' => (string) $user->name, 'Email' => (string) $user->email], $details), null, $request?->ip()));

        } catch (\Throwable $exception) {

            report($exception);

        }

    }

}

