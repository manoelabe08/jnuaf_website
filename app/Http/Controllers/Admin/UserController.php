<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ExportLog;
use App\Models\Territory;
use App\Models\User;
use App\Services\PasswordGenerator;
use App\Mail\AccountAudit;
use App\Mail\AccountCreated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('territory')->whereNull('anonymized_at')->orderBy('role')->orderBy('name')->get(),
            'territories' => Territory::active()->get(),
            'roles' => Role::options(),
            'exports' => ExportLog::with('user')->latest('created_at')->limit(20)->get(),
        ]);
    }

    
    

private function inviteUrl(User $user): string
    {
        $token = \Illuminate\Support\Facades\Password::broker('invitations')->createToken($user);
        return route('invitation.show', ['token' => $token, 'email' => $user->email]);
    }

    public function store(Request $request): RedirectResponse

    {

        $data = $this->validated($request);


        // jnuaf-s45 : seul le super admin cree un compte admin

        if ($data['role'] === Role::Admin->value && ! $request->user()->isSuperAdmin()) {

            return back()->withErrors(['role' => "Seul le super admin peut cr\u{e9}er un compte administrateur."]);

        }


        $plainPassword = PasswordGenerator::generate(16);



        $user = User::create($data + ['password' => $plainPassword]);



        try {

            Mail::to($user->email)->send(new AccountCreated($user, $this->inviteUrl($user)));

            $status = 'Compte créé. Une invitation valable 72 heures a été envoyée à l’adresse email du compte.';

        } catch (\Throwable $exception) {

            report($exception);

            $status = 'Compte créé, mais l\'envoi du mot de passe a échoué. Utilisez « Réinitialiser le mot de passe » pour réessayer.';

        }



        $this->audit('Compte créé', $this->describe($user), $request);



        return back()->with('status', $status);

    }




    public function update(Request $request, User $user): RedirectResponse
    {
        
$data = $this->validated($request, $user);


        // jnuaf-s45 : droits sensibles reserves au super admin

        $s45Actor = $request->user();

        $s45Role = $user->role instanceof Role ? $user->role->value : (string) $user->role;

        $s45OldEmail = (string) $user->email;

        if (! $s45Actor->isSuperAdmin()) {

            if ($data['role'] !== $s45Role) {

                return back()->withErrors(['role' => "Seul le super admin peut changer un r\u{f4}le."]);

            }

            if (! $user->is($s45Actor) && mb_strtolower((string) $data['email']) !== mb_strtolower($s45OldEmail)) {

                return back()->withErrors(['email' => "Seul le super admin peut modifier l'adresse e-mail d'un autre compte."]);

            }

        }


        if ($user->isSuperAdmin()) {

            if (! $user->is($request->user())) {

                abort(403, 'Ce compte est protege.');

            }

            if (mb_strtolower((string) $data['email']) !== mb_strtolower((string) $user->email) || $data['role'] !== Role::Admin->value) {

                return back()->withErrors(['email' => 'Ce compte est protege : son adresse et son role ne peuvent pas etre modifies ici.']);

            }

        }


        if ($user->is($request->user()) && $data['role'] !== Role::Admin->value) {
            return back()->withErrors(['role' => 'Vous ne pouvez pas retirer vos propres droits administrateur.']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        
$before = $this->describe($user);

        $user->update($data);



        if (isset($data['password']) && ! $user->is($request->user())) {

            $user->forceFill(['must_change_password' => true])->save();

        }



        $user->unsetRelation('territory');

        $after = $this->describe($user);


        // jnuaf-s45 : audit du changement d'adresse

        if (mb_strtolower($s45OldEmail) !== mb_strtolower((string) $user->email)) {

            app(\App\Services\SessionService::class)->endAll($user);

            $this->audit("Adresse e-mail modifi\u{e9}e", $after + ['Ancienne adresse' => $s45OldEmail], $request);

        }




        if ($before['Rôle'] !== $after['Rôle'] || $before['Territoire'] !== $after['Territoire']) {
            app(\App\Services\SessionService::class)->endAll($user);

            $this->audit('Rôle ou territoire modifié', $after + ['Ancien rôle' => $before['Rôle'], 'Ancien territoire' => $before['Territoire']], $request);

        }

        if (isset($data['password'])) {

            $this->audit('Mot de passe réinitialisé par un administrateur', $after, $request);

        }


        return back()->with('status', 'Compte mis à jour.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 
'Vous ne pouvez pas supprimer votre propre compte.');

        abort_if($user->isSuperAdmin(), 403, 'Ce compte est protege et ne peut pas etre supprime.');
        abort_if((int) $user->getKey() === 1, 403, 'Ce compte est protege et ne peut pas etre supprime.');

        if (! $request->user()->isSuperAdmin()) {

            return $this->requestDeletion($request, $user);

        }

        
$details = $this->describe($user);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $request) {
            $user->forceFill(['suspended_at' => now(), 'deletion_requested_by' => $request->user()->id, 'deletion_requested_at' => now(), 'anonymize_after' => now()->addDays(30), 'remember_token' => Str::random(60)])->save();
            app(\App\Services\SessionService::class)->endAll($user);
        });

        $this->audit('Compte supprimé', $details, $request);


        return back()->with('status', 'Compte desactive : anonymisation definitive dans 30 jours (restaurable).');
    }

    

public function resetPassword(Request $request, User $user): RedirectResponse

    {
        abort_if($user->isSuperAdmin(), 403, 'Ce compte est protege.');

        abort_if($user->is($request->user()), 422, 'Pour votre propre compte, utilisez « Mon profil » ou « Mot de passe oublié ».');



        $plain = PasswordGenerator::generate(16);

        $user->forceFill([

            'password' => $plain,

            'must_change_password' => false,

            'remember_token' => Str::random(60),

        ])->save();



        if (config('session.driver') === 'database') {

            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

        }



        try {

            Mail::to($user->email)->send(new AccountCreated($user, $this->inviteUrl($user), true));

            $status = 'Une nouvelle invitation a été envoyée à '.$user->email.'.';

        } catch (\Throwable $exception) {

            report($exception);

            $status = 'Le mot de passe a été réinitialisé mais l\'email n\'a pas pu être envoyé. Relancez la réinitialisation.';

        }



        $this->audit('Mot de passe réinitialisé par un administrateur', $this->describe($user), $request);



        return back()->with('status', $status);

    }




    private function requestDeletion(Request $request, User $user): RedirectResponse

    {

        $actor = $request->user();

        $details = $this->describe($user);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user, $actor, $request) {

            $user->forceFill([

                'suspended_at' => now(),

                'deletion_requested_by' => $actor->id,

                'deletion_requested_at' => now(),

                'remember_token' => Str::random(60),

            ])->save();

            app(\App\Services\SessionService::class)->endAll($user);

            $ticket = \App\Models\SensitiveAction::create([

                'user_id' => $actor->id,

                'action_key' => 'confirm_delete_user',

                'method' => 'DELETE',

                'route_name' => 'admin.users.destroy',

                'fingerprint' => hash('sha256', 'confirm_delete_user|'.$user->id.'|'.microtime(true)),

                'target_url' => route('admin.users.index', [], false),

                'status' => 'pending',

                'expires_at' => now()->addDays(365),

                'ip' => $request->ip(),

            ]);

            $ticket->forceFill(['payload' => json_encode(['user_id' => $user->id, 'name' => (string) $user->name, 'email' => (string) $user->email])])->save();

        });

        app(\App\Services\SensitiveActionService::class)->notify($actor, 'Demande de suppression de compte : compte suspendu', ['Compte vise' => (string) $user->email, 'Action' => 'Validation requise du super admin'], $request);

        $this->audit('Compte suspendu : demande de suppression', $details, $request);



        return back()->with('status', 'Le compte est suspendu. Sa suppression doit etre confirmee par le super admin.');

    }    private function describe(User $user): array


    {

        $user->loadMissing('territory');



        return [

            'Nom' => (string) $user->name,

            'Email' => (string) $user->email,

            'Rôle' => $user->role instanceof Role ? $user->role->label() : (string) $user->role,

            'Territoire' => optional($user->territory)->name ?: ($user->territory_id ? '#'.$user->territory_id : 'Tous les territoires'),

        ];

    }



    private function audit(string $action, array $details, Request $request): void

    {

        Log::info('Audit comptes : '.$action, $details + ['acteur_id' => $request->user()?->id]);



        $to = config('jnuaf_audit.account_audit_email');

        if (! $to) {

            return;

        }



        try {

            Mail::to($to)->send(new AccountAudit($action, $details, $request->user(), $request->ip()));

        } catch (\Throwable $exception) {

            report($exception);

        }

    }



    private function validated(
Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user)],
            'role' => ['required', Rule::enum(Role::class)],
            // Une coordination doit être rattachée à un territoire.
            'territory_id' => ['nullable', 'required_if:role,coordinator', Rule::exists('territories', 'id')],
        ]);
    }
}
