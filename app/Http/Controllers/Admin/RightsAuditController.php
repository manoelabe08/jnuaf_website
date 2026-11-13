<?php



namespace App\Http\Controllers\Admin;



use App\Enums\Role;

use App\Http\Controllers\Controller;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Carbon;

use Illuminate\View\View;



class RightsAuditController extends Controller

{

    public function index(Request $request): View

    {

        abort_unless($request->user()->isSuperAdmin(), 404);



        $now = now();

        $toDate = fn ($v) => $v ? Carbon::parse($v) : null;

        $rows = [];



        foreach (User::with('territory')->whereNull('anonymized_at')->orderBy('name')->get() as $u) {

            $role = $u->role instanceof Role ? $u->role : Role::tryFrom((string) $u->role);

            $isAdmin = $role === Role::Admin;

            $last = $toDate($u->last_login_at);

            $created = $toDate($u->created_at);

            $suspended = $toDate($u->suspended_at);

            $expires = $toDate($u->password_expires_at);

            $flags = [];



            if ($isAdmin && ! $u->totp_enabled_at) {

                $flags[] = 'Admin sans second facteur';

            }

            if ($role === Role::Coordinator && ! $u->territory_id) {

                $flags[] = 'Coordination sans ville';

            }

            if (! $last && $created && $created->lt($now->copy()->subDays(14))) {

                $flags[] = "Jamais connect\u{e9} (plus de 14 jours)";

            }

            if ($last && $last->lt($now->copy()->subDays(90))) {

                $flags[] = 'Inactif depuis plus de 90 jours';

            }

            if ($suspended && $u->anonymize_after) {

                $flags[] = 'Suppression en cours';

            } elseif ($suspended) {

                $flags[] = 'Suspendu';

            }

            if ($expires && $expires->isPast()) {

                $flags[] = "Mot de passe expir\u{e9}";

            }



            $rows[] = [

                'name' => (string) $u->name,

                'email' => (string) $u->email,

                'role' => $role ? $role->label() : (string) $u->role,

                'is_admin' => $isAdmin,

                'is_super' => $u->isSuperAdmin(),

                'city' => $u->territory?->city,

                'last_login' => $last ? $last->format('d/m/Y H:i') : null,

                'mfa' => (bool) $u->totp_enabled_at,

                'suspended' => (bool) $suspended,

                'flags' => $flags,

            ];

        }



        usort($rows, fn ($a, $b) => count($b['flags']) <=> count($a['flags']));



        $stats = [

            'total' => count($rows),

            'admins' => count(array_filter($rows, fn ($r) => $r['is_admin'])),

            'admins_sans_mfa' => count(array_filter($rows, fn ($r) => $r['is_admin'] && ! $r['mfa'])),

            'alertes' => count(array_filter($rows, fn ($r) => count($r['flags']) > 0)),

        ];



        return view('admin.audit.rights', ['rows' => $rows, 'stats' => $stats]);

    }

}

