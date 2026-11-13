<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Mail\CommitteePoleProposalMail;

use App\Models\CommitteeApplication;

use App\Models\CommitteePoleProposal;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;

use Illuminate\Validation\Rule;



class CommitteePoleController extends Controller

{

    private function poles(): array

    {

        return array_values(array_filter((array) config('jnuaf.committee.poles'), fn ($p) => $p !== 'Peu importe'));

    }



    public function propose(Request $request, CommitteeApplication $application): RedirectResponse

    {

        $data = $request->validate(['to_pole' => ['required', 'string', Rule::in($this->poles())]]);

        $from = $application->assigned_pole ?: $application->pole;



        if ($data['to_pole'] === $from) {

            return back()->withErrors(['to_pole' => "Ce p\u{f4}le est d\u{e9}j\u{e0} le p\u{f4}le actuel de la personne."]);

        }



        CommitteePoleProposal::where('committee_application_id', $application->id)->where('status', 'pending')->update(['status' => 'cancelled']);



        $raw = Str::random(48);

        $proposal = CommitteePoleProposal::create([

            'committee_application_id' => $application->id,

            'from_pole' => $from,

            'to_pole' => $data['to_pole'],

            'token_hash' => hash('sha256', $raw),

            'status' => 'pending',

            'proposed_by' => $request->user()->id,

            'expires_at' => now()->addDays(7),

        ]);



        try {

            Mail::to($application->email)->send(new CommitteePoleProposalMail($application, $proposal, route('committee.pole.show', $raw)));

            $msg = "Proposition envoy\u{e9}e : la personne a 7 jours pour r\u{e9}pondre.";

        } catch (\Throwable $e) {

            report($e);

            $msg = "Proposition cr\u{e9}\u{e9}e, mais l'e-mail n'a pas pu \u{ea}tre envoy\u{e9} (voir le journal).";

        }



        Log::info('Comite : proposition de pole', ['candidature' => $application->id, 'de' => $from, 'vers' => $data['to_pole'], 'acteur_id' => $request->user()->id]);



        return back()->with('status', $msg);

    }



    public function resolve(Request $request, CommitteeApplication $application): RedirectResponse

    {

        $data = $request->validate(['pole' => ['required', 'string', Rule::in($this->poles())]]);

        $from = $application->assigned_pole ?: $application->pole;



        $application->forceFill(['assigned_pole' => $data['pole']])->save();

        CommitteePoleProposal::syncCardLabel($application, $from, $data['pole']);

        CommitteePoleProposal::where('committee_application_id', $application->id)

            ->whereIn('status', ['pending', 'declined', 'expired'])

            ->update(['status' => 'resolved', 'answered_at' => now()]);



        Log::info('Comite : pole attribue a la main', ['candidature' => $application->id, 'pole' => $data['pole'], 'acteur_id' => $request->user()->id]);



        return back()->with('status', "P\u{f4}le attribu\u{e9}.");

    }

}

