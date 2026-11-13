<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Symfony\Component\HttpFoundation\Response;



class GuardAccounts

{

    private const ADMIN_ONLY_WRITES = ['admin.events.*', 'admin.cards.*'];



    public function handle(Request $request, Closure $next): Response

    {

        $user = $request->user();

        if ($user && $user->suspended_at) {

            Auth::guard()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();



            return redirect(url('/admin/connexion'))->with('status', 'Ce compte est suspendu. Contactez le Bureau de Pilotage.');

        }

        
if ($user && ! $user->committeeAccessValid()) {

            app(\App\Services\CommitteeAccessService::class)->revoke($user, 'controle a chaque requete');

            Auth::guard()->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();



            return redirect(url('/admin/connexion'))->with('status', 'Ce compte n a plus acces. Contactez le Bureau de Pilotage.');

        }



        if ($user && ! $user->isAdmin() && ! $request->isMethodSafe()
 && $request->routeIs(...self::ADMIN_ONLY_WRITES)) {

            abort(403);

        }



        return $next($request);

    }

}

