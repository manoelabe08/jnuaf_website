<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\SensitiveAction;

use App\Services\SensitiveActionService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\View\View;



class SensitiveActionController extends Controller

{

    public function challenge(Request $request): View|RedirectResponse

    {

        $challenge = $request->session()->get('sensitive.challenge');

        if (! $challenge) {

            return redirect()->route('admin.dashboard');

        }



        return view('admin.sensitive.challenge', ['challenge' => $challenge, 'hasTotp' => $request->user()->hasTotp(), 'isSuper' => $request->user()->isSuperAdmin()]);

    }



    public function verify(Request $request, SensitiveActionService $svc): RedirectResponse

    {

        $challenge = $request->session()->get('sensitive.challenge');

        if (! $challenge) {

            return redirect()->route('admin.dashboard');

        }



        $request->validate(['code' => ['required', 'string', 'max:12']]);

        $user = $request->user();



        $result = $svc->checkCode($request, $user, (string) $request->input('code'));

        if ($result !== 'ok') {

            return back()->withErrors(['code' => $this->codeError($result)]);

        }



        $request->session()->forget('sensitive.challenge');



        if ((int) $challenge['level'] >= 2) {

            if ($user->isSuperAdmin()) {

                $request->session()->put('sensitive.fresh.'.$challenge['fingerprint'], time());

            } else {

                $svc->requestApproval($request, $user, $challenge['key'], $challenge['fingerprint'], (string) $challenge['url'], (string) $challenge['method']);



                return redirect()->route('admin.sensitive.index')->with('status', "Demande envoy\u{e9}e au Bureau de Pilotage. Vous pourrez relancer l'action une fois la validation donn\u{e9}e.");

            }

        } else {

            $request->session()->put('sensitive.verified_at', time());

        }



        if ($challenge['method'] === 'GET') {

            return redirect()->to($challenge['url']);

        }



        return redirect()->to($challenge['back'] ?: route('admin.dashboard'))->with('status', "Code valid\u{e9}. Relancez l'action dans les 5 minutes.");

    }



    public function index(Request $request, SensitiveActionService $svc): View

    {

        SensitiveAction::whereIn('status', ['pending', 'approved'])->where('expires_at', '<', now())->update(['status' => 'expired']);



        $user = $request->user();

        $query = SensitiveAction::with(['user', 'decider'])->latest('id');

        if (! $user->isSuperAdmin()) {

            $query->where('user_id', $user->id);

        }



        return view('admin.sensitive.index', ['actions' => $query->paginate(25), 'isSuper' => $user->isSuperAdmin(), 'svc' => $svc]);

    }



    public function approve(Request $request, SensitiveAction $action, SensitiveActionService $svc): RedirectResponse

    {

        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);

        abort_unless($action->status === 'pending' && $action->expires_at->isFuture(), 422, "Demande expir\u{e9}e ou d\u{e9}j\u{e0} trait\u{e9}e.");

        abort_if($action->user_id === $user->id, 422);



        $request->validate(['code' => ['required', 'string', 'max:12']]);

        $result = $svc->checkCode($request, $user, (string) $request->input('code'));

        if ($result !== 'ok') {

            return back()->withErrors(['code' => $this->codeError($result)]);

        }



        
if ($action->action_key === 'confirm_delete_user') {

            $payload = json_decode((string) $action->payload, true) ?: [];

            $target = \App\Models\User::find((int) ($payload['user_id'] ?? 0));

            if ($target && ! $target->isSuperAdmin()) {

                $target->forceFill(['suspended_at' => $target->suspended_at ?: now(), 'anonymize_after' => now()->addDays(30)])->save();

            }

            $action->forceFill(['status' => 'consumed', 'decided_by' => $user->id, 'decided_at' => now(), 'consumed_at' => now()])->save();

            $svc->notify($user, 'Compte supprime apres validation', ['Demandeur' => (string) $action->user?->email, 'Compte' => (string) ($payload['email'] ?? '')], $request);



            return back()->with('status', 'Suppression confirmee : anonymisation definitive dans 30 jours (compte restaurable).');

        }

        $action->forceFill(['status' => 'approved'
, 'decided_by' => $user->id, 'decided_at' => now(), 'expires_at' => now()->addHours(SensitiveActionService::APPROVAL_HOURS)])->save();

        $svc->notify($user, "Action critique approuv\u{e9}e", ['Demandeur' => (string) $action->user?->email, 'Action' => $svc->label($action->action_key)], $request);



        return back()->with('status', "Demande approuv\u{e9}e.");

    }



    public function reject(Request $request, SensitiveAction $action, SensitiveActionService $svc): RedirectResponse

    {

        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);

        abort_unless(in_array($action->status, ['pending', 'approved'], true), 422);



        
if ($action->action_key === 'confirm_delete_user') {

            $payload = json_decode((string) $action->payload, true) ?: [];

            $target = \App\Models\User::find((int) ($payload['user_id'] ?? 0));

            if ($target) {

                $target->forceFill(['suspended_at' => null, 'deletion_requested_by' => null, 'deletion_requested_at' => null])->save();

                $svc->notify($user, 'Compte reactive apres refus de suppression', ['Compte' => (string) $target->email], $request);

            }

        }

        $action->forceFill(['status' => 'rejected'
, 'decided_by' => $user->id, 'decided_at' => now()])->save();

        $svc->notify($user, "Action critique refus\u{e9}e", ['Demandeur' => (string) $action->user?->email, 'Action' => $svc->label($action->action_key)], $request);



        return back()->with('status', "Demande refus\u{e9}e.");

    }



    public function bulk(Request $request, SensitiveActionService $svc): RedirectResponse

    {

        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);

        $data = $request->validate([

            'op' => ['required', 'in:approve,reject'],

            'ids' => ['required', 'array', 'min:1', 'max:50'],

            'ids.*' => ['integer'],

        ]);

        if ($data['op'] === 'approve') {

            $request->validate(['code' => ['required', 'string', 'max:12']]);

            $result = $svc->checkCode($request, $user, (string) $request->input('code'));

            if ($result !== 'ok') {

                return back()->withErrors(['code' => $this->codeError($result)]);

            }

        }

        $done = 0;

        $skipped = 0;

        $actions = \App\Models\SensitiveAction::whereIn('id', array_unique($data['ids']))->get();

        foreach ($actions as $action) {

            if ($data['op'] === 'approve') {

                $ok = $action->status === 'pending' && $action->expires_at->isFuture() && $action->user_id !== $user->id && $action->action_key !== 'confirm_delete_user';

                if (! $ok) {

                    $skipped++;

                    continue;

                }

                $action->forceFill(['status' => 'approved', 'decided_by' => $user->id, 'decided_at' => now(), 'expires_at' => now()->addHours(\App\Services\SensitiveActionService::APPROVAL_HOURS)])->save();

                $done++;

            } else {

                if (! in_array($action->status, ['pending', 'approved'], true)) {

                    $skipped++;

                    continue;

                }

                if ($action->action_key === 'confirm_delete_user') {

                    $payload = json_decode((string) $action->payload, true) ?: [];

                    $target = \App\Models\User::find((int) ($payload['user_id'] ?? 0));

                    if ($target) {

                        $target->forceFill(['suspended_at' => null, 'deletion_requested_by' => null, 'deletion_requested_at' => null])->save();

                    }

                }

                $action->forceFill(['status' => 'rejected', 'decided_by' => $user->id, 'decided_at' => now()])->save();

                $done++;

            }

        }

        try {

            $svc->notify($user, 'Validations en masse', ['Operation' => $data['op'] === 'approve' ? 'Approbation' : 'Refus', 'Traitees' => (string) $done, 'Ignorees' => (string) $skipped], $request);

        } catch (\Throwable $e) {

            report($e);

        }

        $msg = $done.' demande(s) traitee(s)';

        if ($skipped) {

            $msg .= ', '.$skipped.' ignoree(s) (deja traitee, expiree, votre propre demande ou suppression de compte a valider une par une)';

        }

        return back()->with('status', $msg.'.');

    }

    private function codeError(string $result): string

    {

        return match ($result) {

            'no_totp' => "Activez d'abord votre application d'authentification dans votre profil.",

            'locked' => 'Trop de tentatives : r'."\u{e9}essayez dans 15 minutes.",

            default => "Code incorrect ou d\u{e9}j\u{e0} utilis\u{e9} : attendez le code suivant (30 secondes) puis r\u{e9}essayez.",

        };

    }

}

