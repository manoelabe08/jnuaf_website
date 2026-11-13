<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\CommitteeApplication;

use App\Services\CommitteeAccountService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;



class CommitteeAccountController extends Controller

{

    public function store(Request $request, CommitteeApplication $application, CommitteeAccountService $service): RedirectResponse

    {

        abort_unless($request->user()?->isAdmin(), 403);



        try {

            $user = $service->create($application, $request->user(), $request);

        } catch (\DomainException $e) {

            return back()->with('status', $e->getMessage());

        }



        return back()->with('status', $service->mailSent

            ? 'Compte cree. Une invitation valable 72 heures a ete envoyee a '.$user->email.'.'

            : "Compte cree, mais l'envoi de l'invitation a echoue. Utilisez Reinitialiser le mot de passe sur la page Comptes.");

    }

}

