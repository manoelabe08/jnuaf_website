<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\User;

use App\Services\TrashService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\View\View;



class TrashController extends Controller

{

    public function index(Request $request): View

    {

        abort_unless($request->user()->isSuperAdmin(), 403);

        $items = DB::table('trash_items')->whereNull('restored_at')->orderByDesc('id')->limit(300)

            ->get(['id', 'model', 'original_id', 'label', 'deleted_by', 'deleted_at', 'purge_at']);

        $names = User::whereIn('id', $items->pluck('deleted_by')->filter()->unique()->all())->pluck('name', 'id');

        $labels = [

            'Registration' => 'Inscription',

            'Event' => "\u{c9}v\u{e9}nement",

            'CommitteeApplication' => "Candidature au comit\u{e9}",

            'PartnerInquiry' => 'Demande de partenaire',
            'Territory' => 'Territoire',

        ];



        return view('admin.trash.index', ['items' => $items, 'names' => $names, 'labels' => $labels]);

    }



    public function restore(Request $request, int $id): RedirectResponse

    {

        abort_unless($request->user()->isSuperAdmin(), 403);

        $message = TrashService::restore($id);

        Log::info('Corbeille : restauration', ['id' => $id, 'acteur_id' => $request->user()->id, 'resultat' => $message]);



        return back()->with('status', $message);

    }



    public function purge(Request $request, int $id): RedirectResponse

    {

        abort_unless($request->user()->isSuperAdmin(), 403);

        \App\Services\TrashService::purgeOne($id);

        Log::info('Corbeille : suppression definitive', ['id' => $id, 'acteur_id' => $request->user()->id]);



        return back()->with('status', 'Element supprime definitivement de la corbeille.');

    }




    public function bulk(Request $request): RedirectResponse

{

    abort_unless($request->user()->isSuperAdmin(), 403);

    $data = $request->validate([

        'action' => ['required', 'in:restore,purge'],

        'ids' => ['required', 'array', 'min:1', 'max:100'],

        'ids.*' => ['integer'],

    ]);

    $ok = 0;

    $fail = 0;

    foreach (array_unique($data['ids']) as $id) {

        try {

            if ($data['action'] === 'restore') {

                TrashService::restore((int) $id);

            } else {

                TrashService::purgeOne((int) $id);

            }

            $ok++;

        } catch (\Throwable $e) {

            report($e);

            $fail++;

        }

    }

    Log::info('Corbeille : action en masse', ['action' => $data['action'], 'ids' => array_values(array_unique($data['ids'])), 'ok' => $ok, 'echecs' => $fail, 'acteur_id' => $request->user()->id]);

    $label = $data['action'] === 'restore' ? 'restaures' : 'supprimes definitivement';

    return back()->with('status', $ok.' element(s) '.$label.($fail ? ', '.$fail.' en echec' : '').'.');

}

    public function reactivate(Request $request, User $user): RedirectResponse

    {

        abort_unless($request->user()->isSuperAdmin(), 403);

        abort_if($user->anonymized_at, 422, 'Ce compte est anonymise.');

        $user->forceFill(['suspended_at' => null, 'deletion_requested_by' => null, 'deletion_requested_at' => null, 'anonymize_after' => null])->save();

        \App\Models\SensitiveAction::where('action_key', 'confirm_delete_user')->where('status', 'pending')

            ->where('payload', 'like', '%"user_id":'.$user->id.',%')->update(['status' => 'rejected']);

        Log::info('Compte reactive', ['compte_id' => $user->id, 'acteur_id' => $request->user()->id]);



        return back()->with('status', 'Compte reactive.');

    }

}


