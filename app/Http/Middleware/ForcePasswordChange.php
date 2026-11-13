<?php

namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class ForcePasswordChange

{

    public function handle(Request $request, Closure $next): Response

    {

        $user = $request->user();



        if ($user && $user->must_change_password

            && ! $request->routeIs('logout', 'admin.password.change', 'admin.password.change.update')) {

            if ($request->expectsJson()) {

                return response()->json(['message' => 'Changement de mot de passe requis.'], 423);

            }



            return redirect()->route('admin.password.change');

        }



        return $next($request);

    }

}

