<?php



namespace App\Http\Controllers;



use App\Services\EmailChangeService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;



class EmailChangeController extends Controller

{

    public function request(Request $request, EmailChangeService $service): RedirectResponse

    {

        $user = $request->user();



        $data = $request->validate([

            'new_email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],

            'current_password' => ['required', 'current_password'],

        ], [

            'new_email.required' => 'Indiquez la nouvelle adresse email.',

            'new_email.email' => 'Adresse email invalide.',

            'new_email.unique' => 'Cette adresse est déjà utilisée par un autre compte.',

            'current_password.required' => 'Saisissez votre mot de passe pour confirmer.',

            'current_password.current_password' => 'Le mot de passe est incorrect.',

        ]);



        $new = mb_strtolower(trim($data['new_email']));

        if ($new === mb_strtolower($user->email)) {

            return back()->withErrors(['new_email' => 'C’est déjà votre adresse actuelle.']);

        }



        $service->start($user, $new, $request->ip());



        return back()->with('status', $user->isAdmin()

            ? 'Un lien de confirmation a été envoyé à la nouvelle adresse (valable 60 minutes). Après confirmation, la demande sera soumise à validation du Bureau de Pilotage.'

            : 'Un lien de confirmation a été envoyé à la nouvelle adresse (valable 60 minutes).');

    }



    public function cancel(Request $request, EmailChangeService $service): RedirectResponse

    {

        $service->cancel($request->user());



        return back()->with('status', 'Demande de changement d’email annulée.');

    }



    public function showConfirm(string $token, EmailChangeService $service)

    {

        $req = $service->findByConfirmToken($token);



        if (! $req || $req->status !== 'pending_confirmation' || $req->expires_at->isPast()) {

            return $this->result(false, 'Ce lien est invalide, déjà utilisé ou expiré.');

        }



        return $this->page([

            'mode' => 'confirm',

            'title' => 'Confirmer votre nouvelle adresse email',

            'details' => ['Compte' => $req->user->name, 'Ancienne adresse' => $req->old_email, 'Nouvelle adresse' => $req->new_email],

            'action' => route('email-change.confirm.do', ['token' => $token]),

        ]);

    }



    public function doConfirm(Request $request, string $token, EmailChangeService $service)

    {

        $result = $service->confirm($token, $request->ip());



        return $this->result($result['ok'], $result['message']);

    }



    public function showDecision(string $token, EmailChangeService $service)

    {

        $req = $service->findByApprovalToken($token);



        if (! $req || $req->status !== 'pending_approval' || $req->expires_at->isPast()) {

            return $this->result(false, 'Ce lien est invalide, déjà utilisé ou expiré.');

        }



        return $this->page([

            'mode' => 'decision',

            'title' => 'Changement d’email d’un compte à accès complet',

            'details' => [

                'Compte' => $req->user->name,

                'Rôle' => $req->user->role?->label(),

                'Adresse actuelle' => $req->old_email,

                'Nouvelle adresse (confirmée)' => $req->new_email,

                'Demande faite le' => $req->created_at->timezone(config('jnuaf_newsletter.timezone', 'Europe/Paris'))->format('d/m/Y à H:i'),

                'Adresse IP' => $req->ip ?? '-',

            ],

            'action' => route('email-change.decision.do', ['token' => $token]),

        ]);

    }



    public function doDecision(Request $request, string $token, EmailChangeService $service)

    {

        $decision = $request->input('decision');

        abort_unless(in_array($decision, ['approve', 'refuse'], true), 422);



        $result = $service->decide($token, $decision, $request->ip(), $request->user());



        return $this->result($result['ok'], $result['message']);

    }



    private function result(bool $ok, string $message)

    {

        return $this->page(['mode' => 'result', 'ok' => $ok, 'title' => $ok ? 'Terminé' : 'Lien non valable', 'message' => $message]);
    }

    private function page(array $data)
    {
        return response()
            ->view('admin.email-change.page', $data + ['details' => [], 'action' => null, 'message' => null, 'ok' => true])
            ->header('Cache-Control', 'no-store, private')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
