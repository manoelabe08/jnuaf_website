<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;



class MailSuggestController extends Controller

{

    public function index(Request $request): JsonResponse

    {

        $user = $request->user();

        $role = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        abort_unless($role === 'admin', 403);



        $q = mb_strtolower(trim((string) $request->query('q', '')));

        if (mb_strlen($q) < 2 || mb_strlen($q) > 64) {

            return response()->json([]);

        }

        $like = addcslashes($q, '%_\\').'%';

        $inner = '% '.$like;

        $found = [];

        $add = function (?string $email, ?string $name) use (&$found) {

            $email = mb_strtolower(trim((string) $email));

            if ($email === '' || str_ends_with($email, '.invalid') || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {

                return;

            }

            if (! isset($found[$email])) {

                $found[$email] = trim((string) $name);

            }

        };



        $rows = DB::table('users')

            ->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('name', 'like', $like)->orWhere('name', 'like', $inner))

            ->limit(8)->get(['email', 'name']);

        foreach ($rows as $r) {

            $add($r->email, $r->name);

        }



        $rows = DB::table('registrations')->whereNull('anonymized_at')

            ->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like))

            ->limit(8)->get(['email', 'first_name', 'last_name']);

        foreach ($rows as $r) {

            $add($r->email, trim($r->first_name.' '.$r->last_name));

        }



        $rows = DB::table('committee_applications')

            ->where(fn ($w) => $w->where('email', 'like', $like)->orWhere('full_name', 'like', $like)->orWhere('full_name', 'like', $inner))

            ->limit(8)->get(['email', 'full_name']);

        foreach ($rows as $r) {

            $add($r->email, $r->full_name);

        }



        $rows = DB::table('member_cards')->whereNull('anonymized_at')->where('email', 'like', $like)->limit(8)->get(['email']);

        foreach ($rows as $r) {

            $add($r->email, null);

        }



        $out = [];

        foreach (array_slice($found, 0, 8, true) as $email => $name) {

            $out[] = ['email' => $email, 'name' => $name];

        }



        return response()->json($out)->header('Cache-Control', 'private, no-store');

    }

}

