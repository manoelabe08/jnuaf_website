<?php

use App\Http\Middleware\AdminIdleTimeout;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\NoIndexWhenStaging;
use App\Http\Middleware\RequireAdminTotp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: fn () => require base_path('routes/deploy.php'),
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['admin' => EnsureUserIsAdmin::class, 'sensitive' => \App\Http\Middleware\RequireSensitiveAction::class, 'page' => \App\Http\Middleware\EnsurePageIsPublished::class]);
        $middleware->appendToGroup('web', \App\Http\Middleware\GuardAccounts::class);
        $middleware->web(append: [NoIndexWhenStaging::class, ForcePasswordChange::class, SecurityHeaders::class, AdminIdleTimeout::class, RequireAdminTotp::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('admin') || $request->is('admin/*')) {
                
// jnuaf-notification-login-v1

                if ($request->expectsJson()) {

                    return response()->json(['message' => 'Authentification requise.'], 401);

                }



                if ($request->isMethodSafe() && $request->hasSession()) {

                    $destination = $request->getPathInfo().($request->getQueryString() ? '?'.$request->getQueryString() : '');



                    if (str_starts_with($destination, '/')

                        && ! str_starts_with($destination, '//')

                        && ! preg_match('/[\x00-\x20\x7f\\\\]/', $destination)) {

                        $request->session()->put('url.intended', $destination);



                        return redirect()->route('account.show');

                    }

                }



                return response()->view('errors.404', [], 404);

            }
        });
    })->create();
