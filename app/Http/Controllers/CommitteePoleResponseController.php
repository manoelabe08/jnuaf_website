<?php



namespace App\Http\Controllers;



use App\Models\CommitteeApplication;

use App\Models\CommitteePoleProposal;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\View\View;



class CommitteePoleResponseController extends Controller

{

    public function show(string $token): View

    {

        $p = CommitteePoleProposal::where('token_hash', hash('sha256', $token))->first();

        $valid = $p && $p->status === 'pending' && $p->expires_at && $p->expires_at->isFuture();



        return view('committee.pole-response', [

            'state' => $valid ? 'ask' : 'invalid',

            'proposal' => $valid ? $p : null,

            'application' => $valid ? CommitteeApplication::find($p->committee_application_id) : null,

        ]);

    }



    public function answer(Request $request, string $token): View

    {

        $data = $request->validate(['choice' => ['required', 'in:accept,decline']]);



        $result = DB::transaction(function () use ($token, $data) {

            $p = CommitteePoleProposal::where('token_hash', hash('sha256', $token))->lockForUpdate()->first();

            if (! $p || $p->status !== 'pending' || ! $p->expires_at || $p->expires_at->isPast()) {

                return null;

            }

            $app = CommitteeApplication::find($p->committee_application_id);

            if (! $app) {

                return null;

            }

            if ($data['choice'] === 'accept') {

                $app->forceFill(['assigned_pole' => $p->to_pole])->save();

                CommitteePoleProposal::syncCardLabel($app, $p->from_pole, $p->to_pole);

                $p->forceFill(['status' => 'accepted', 'answered_at' => now()])->save();

            } else {

                $p->forceFill(['status' => 'declined', 'answered_at' => now()])->save();

            }



            return ['p' => $p, 'app' => $app];

        });



        if (! $result) {

            return view('committee.pole-response', ['state' => 'invalid', 'proposal' => null, 'application' => null]);

        }



        try {

            $to = config('jnuaf.notify_email');

            if ($to) {

                $text = 'Candidature comite : '.$result['app']->full_name."\nProposition : ".$result['p']->from_pole.' -> '.$result['p']->to_pole."\nReponse : ".($data['choice'] === 'accept' ? 'ACCEPTEE' : 'REFUSEE (conflit a resoudre)');

                Mail::raw($text, fn ($m) => $m->to($to)->subject('Candidature comite : reponse a une proposition de pole'));

            }

        } catch (\Throwable $e) {

            report($e);

        }



        return view('committee.pole-response', [

            'state' => $data['choice'] === 'accept' ? 'accepted' : 'declined',

            'proposal' => $result['p'],

            'application' => $result['app'],

        ]);

    }

}

