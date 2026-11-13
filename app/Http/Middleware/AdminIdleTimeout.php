<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use Symfony\Component\HttpFoundation\Response;



class AdminIdleTimeout

{

    public function handle(Request $request, Closure $next): Response

    {

        $user = $request->user();



        if ($user && $user->isAdmin()) {

            $minutes = max(1, (int) config('jnuaf_security.admin_idle_minutes', 30));

            $last = (int) $request->session()->get('last_activity', 0);



            if ($last > 0 && now()->timestamp - $last > $minutes * 60) {

                Auth::logout();

                $request->session()->invalidate();

                $request->session()->regenerateToken();



                if ($request->expectsJson()) {

                    return response()->json(['message' => 'Session expirée.'], 401);

                }



                return redirect()->route('login')->withErrors([

                    'email' => 'Session fermée après '.$minutes.' minutes d’inactivité. Reconnectez-vous.',

                ]);

            }



            if (! $request->routeIs('admin.chat.poll', 'admin.chat.unread')) {

                $request->session()->put('last_activity', now()->timestamp);

            }

        }



        return $next($request);

    }

}

