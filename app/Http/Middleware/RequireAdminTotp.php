<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class RequireAdminTotp

{

    public function handle(Request $request, Closure $next): Response

    {

        $user = $request->user();



        if ($user && $user->isAdmin()

            && config('jnuaf_security.admin_totp_required', true)

            && ! $user->hasTotp()

            && ! $request->routeIs(

                'logout', 'admin.profile', 'admin.profile.totp.*', 'admin.profile.avatar',

                'admin.password.change', 'admin.password.change.update'

            )) {

            if ($request->expectsJson()) {

                return response()->json(['message' => 'Configurez votre application d’authentification.'], 423);

            }



            return redirect()->route('admin.profile')->with('status', 'Pour protéger votre compte à accès complet, configurez d’abord votre application d’authentification (section « Application d’authentification »).');

        }



        return $next($request);

    }

}

