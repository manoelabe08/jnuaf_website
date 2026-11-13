<?php



namespace App\Http\Middleware;



use App\Services\SensitiveActionService;

use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class RequireSensitiveAction

{

    public function handle(Request $request, Closure $next, string $level = '1', string $key = 'action'): Response

    {

        $user = $request->user();

        abort_unless($user, 403);



        $svc = app(SensitiveActionService::class);

        $level = (int) $level;

        $fingerprint = $svc->fingerprint($request, $key);

        $label = $svc->label($key);

        $done = fn (string $title) => $svc->notify($user, $title, ['Action' => $label, 'Page' => '/'.$request->path()], $request);



        if ($level >= 2) {

            if ($user->isSuperAdmin()) {

                if ($svc->takeFresh($request, $fingerprint)) {

                    $done("Action critique ex\u{e9}cut\u{e9}e");



                    return $next($request);

                }

            } else {

                $ticket = $svc->approvedTicket($user, $fingerprint);

                if ($ticket) {

                    $ticket->forceFill(['status' => 'consumed', 'consumed_at' => now()])->save();

                    $done("Action critique ex\u{e9}cut\u{e9}e");



                    return $next($request);

                }

                if ($svc->pendingTicket($user, $fingerprint)) {

                    return redirect()->route('admin.sensitive.index')->with('status', "Demande d\u{e9}j\u{e0} envoy\u{e9}e : en attente de validation du Bureau de Pilotage.");

                }

            }

        } elseif ($svc->recentlyVerified($request)) {

            $done("Action sensible ex\u{e9}cut\u{e9}e");



            return $next($request);

        }



        $back = $request->isMethod('GET') ? null : url()->previous();

        if ($back !== null && ! str_starts_with($back, url('/'))) {

            $back = null;

        }



        $request->session()->put('sensitive.challenge', [

            'key' => $key,

            'label' => $label,

            'level' => $level,

            'fingerprint' => $fingerprint,

            'method' => $request->method(),

            'url' => $request->fullUrl(),

            'back' => $back,

        ]);



        return redirect()->route('admin.sensitive.challenge');

    }

}

