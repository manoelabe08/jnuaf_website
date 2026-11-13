<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Services\CommitteeInvitationService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;



class CommitteeInvitationController extends Controller

{

    public function preview(Request $request, CommitteeInvitationService $svc): RedirectResponse

    {

        abort_unless($request->user()?->isAdmin(), 403);



        $data = $request->validate(['emails' => ['required', 'string', 'max:3000']]);

        $emails = $svc->parse($data['emails']);



        if (count($emails) > CommitteeInvitationService::MAX_BATCH) {

            return back()->with('status', 'Pas plus de '.CommitteeInvitationService::MAX_BATCH.' adresses a la fois.');

        }



        session(['invite_plan' => $svc->plan($emails)]);



        return back();

    }



    public function send(Request $request, CommitteeInvitationService $svc): RedirectResponse

    {

        abort_unless($request->user()?->isAdmin(), 403);



        $plan = session('invite_plan');

        if (! is_array($plan) || empty($plan['todo'])) {

            return back()->with('status', "Aucune invitation preparee. Preparez d'abord la liste.");

        }



        try {

            $sent = $svc->send($plan['todo'], $request->user(), $request);

        } catch (\DomainException $e) {

            return back()->with('status', $e->getMessage());

        }



        session()->forget('invite_plan');



        return back()->with('status', $sent.' invitation(s) envoyee(s) sur '.count($plan['todo']).' preparee(s).');

    }



    public function cancel(Request $request): RedirectResponse

    {

        abort_unless($request->user()?->isAdmin(), 403);

        session()->forget('invite_plan');



        return back();

    }

}

