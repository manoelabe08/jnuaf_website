<?php

namespace App\Services;

use App\Mail\AccountAudit;
use App\Mail\EmailChangeMail;
use App\Models\EmailChangeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmailChangeService
{
    public const CONFIRM_MINUTES = 60;
    public const APPROVAL_HOURS = 48;

    private ?User $currentActor = null;

    public function findByConfirmToken(string $token): ?EmailChangeRequest
    {
        return EmailChangeRequest::with('user')->where('confirm_token_hash', hash('sha256', $token))->first();
    }

    public function findByApprovalToken(string $token): ?EmailChangeRequest
    {
        return EmailChangeRequest::with('user')->where('approval_token_hash', hash('sha256', $token))->first();
    }

    public function pendingFor(User $user): ?EmailChangeRequest
    {
        return EmailChangeRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending_confirmation', 'pending_approval'])
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    public function start(User $user, string $newEmail, ?string $ip): EmailChangeRequest
    {
        EmailChangeRequest::where('user_id', $user->id)
            ->whereIn('status', ['pending_confirmation', 'pending_approval'])
            ->update(['status' => 'cancelled']);

        $token = bin2hex(random_bytes(32));
        $request = EmailChangeRequest::create([
            'user_id' => $user->id,'old_email' => $user->email,

            'new_email' => $newEmail,

            'status' => 'pending_confirmation',

            'requires_approval' => $user->isAdmin(),

            'confirm_token_hash' => hash('sha256', $token),

            'expires_at' => now()->addMinutes(self::CONFIRM_MINUTES),

            'ip' => $ip,

        ]);



        $sent = $this->mail($newEmail, new EmailChangeMail(

            'confirm', $user, $user->email, $newEmail, route('email-change.confirm', ['token' => $token]), $ip, $request->requires_approval

        ));



        if (! $sent) {

            $request->update(['status' => 'cancelled']);

            throw ValidationException::withMessages(['new_email' => 'Impossible d’envoyer l’email de confirmation à cette adresse.']);

        }



        $this->mail($user->email, new EmailChangeMail('requested', $user, $user->email, $newEmail, null, $ip, $request->requires_approval));



        if ($user->isAdmin()) {

            $this->audit('Demande de changement d’email (compte à accès complet)', $user, $user->email, $newEmail, $user, $ip);

        }



        Log::info('Changement d’email demandé', ['user_id' => $user->id, 'admin' => $user->isAdmin()]);



        return $request;

    }



    public function cancel(User $user): void

    {

        EmailChangeRequest::where('user_id', $user->id)

            ->whereIn('status', ['pending_confirmation', 'pending_approval'])

            ->update(['status' => 'cancelled']);

    }



    public function confirm(string $token, ?string $ip): array

    {

        return DB::transaction(function () use ($token, $ip) {

            $request = EmailChangeRequest::where('confirm_token_hash', hash('sha256', $token))->lockForUpdate()->first();



            if (! $request || $request->status !== 'pending_confirmation' || $request->expires_at->isPast()) {

                return ['ok' => false, 'message' => 'Ce lien est invalide, déjà utilisé ou expiré.'];

            }



            $user = User::find($request->user_id);

            if (! $user || User::where('email', $request->new_email)->where('id', '!=', $user->id)->exists()) {

                $request->update(['status' => 'cancelled']);



                return ['ok' => false, 'message' => 'Cette adresse n’est plus disponible.'];

            }



            if (! $request->requires_approval) {

                $request->update(['confirmed_at' => now()]);

                $this->apply($request, $user, $ip);



                return ['ok' => true, 'message' => 'Votre adresse email a été modifiée. Utilisez-la dès maintenant pour vous connecter.'];

            }



            $approvalToken = bin2hex(random_bytes(32));

            $request->update([

                'status' => 'pending_approval',

                'confirmed_at' => now(),

                'approval_token_hash' => hash('sha256', $approvalToken),

                'expires_at' => now()->addHours(self::APPROVAL_HOURS),

            ]);



            $auditTo = config('jnuaf_audit.account_audit_email');

            if ($auditTo) {

                $this->mail($auditTo, new EmailChangeMail(

                    'approval', $user, $request->old_email, $request->new_email,

                    route('email-change.decision', ['token' => $approvalToken]), $request->ip, true

                ));

            }



            return ['ok' => true, 'message' => 'Adresse confirmée. Votre demande doit maintenant être validée par le Bureau de Pilotage ; vous serez prévenu par email. Tant qu’elle n’est pas validée, votre adresse actuelle reste active.'];

        });

    }



    public function decide(string $token, string $decision, ?string $ip, ?User $actor = null): array

    {

        $this->currentActor = $actor;

        return DB::transaction(function () use ($token, $decision, $ip) {

            $request = EmailChangeRequest::where('approval_token_hash', hash('sha256', $token))->lockForUpdate()->first();



            if (! $request || $request->status !== 'pending_approval' || $request->expires_at->isPast()) {

                return ['ok' => false, 'message' => 'Ce lien est invalide, déjà utilisé ou expiré.'];

            }



            $user = User::find($request->user_id);

            if (! $user) {

                $request->update(['status' => 'cancelled']);



                return ['ok' => false, 'message' => 'Le compte n’existe plus.'];

            }



            if ($decision === 'refuse') {

                $request->update(['status' => 'refused', 'decided_at' => now()]);

                $this->mail($request->old_email, new EmailChangeMail('refused', $user, $request->old_email, $request->new_email));

                $this->audit('Changement d’email refusé', $user, $request->old_email, $request->new_email, $this->currentActor, $ip);



                return ['ok' => true, 'message' => 'Demande refusée. L’adresse actuelle reste inchangée et l’utilisateur a été prévenu.'];

            }



            if (User::where('email', $request->new_email)->where('id', '!=', $user->id)->exists()) {

                $request->update(['status' => 'cancelled']);



                return ['ok' => false, 'message' => 'Cette adresse est déjà utilisée par un autre compte : demande annulée.'];

            }



            $this->apply($request, $user, $ip);



            return ['ok' => true, 'message' => 'Demande acceptée. L’adresse du compte a été modifiée et les deux adresses ont été prévenues.'];

        });

    }



    private function apply(EmailChangeRequest $request, User $user, ?string $ip): void

    {

        $old = $user->email;



        $user->forceFill([

            'email' => $request->new_email,

            'email_verified_at' => now(),

            'remember_token' => Str::random(60),

        ])->save();



        $request->update(['status' => 'applied', 'decided_at' => now()]);
        app(\App\Services\SessionService::class)->endAll($user);



        $this->mail($old, new EmailChangeMail('applied_old', $user, $old, $request->new_email, null, $ip));

        $this->mail($request->new_email, new EmailChangeMail('applied_new', $user, $old, $request->new_email, null, $ip));



        if ($request->requires_approval) {

            $this->audit('Changement d’email appliqué (compte à accès complet)', $user, $old, $request->new_email, $this->currentActor, $ip);

        }



        Log::info('Changement d’email appliqué', ['user_id' => $user->id, 'admin' => $request->requires_approval]);

    }



    private function audit(string $action, User $user, string $old, string $new, ?User $actor, ?string $ip): void

    {

        $to = config('jnuaf_audit.account_audit_email');

        if (! $to) {

            return;

        }



        $this->mail($to, new AccountAudit($action, [

            'Compte' => (string) $user->name,

            'Rôle' => $user->role?->label() ?? '',

            'Ancien email' => $old,

            'Nouvel email' => $new,

        ], $actor, $ip));

    }



    private function mail(string $to, $mailable): bool

    {

        try {

            Mail::to($to)->send($mailable);



            return true;

        } catch (\Throwable $exception) {

            report($exception);



            return false;

        }

    }

}

