<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sur un environnement de test (ex. test.jnuaf.org), demande aux moteurs de recherche
 * de ne rien indexer, pour éviter que la version de test concurrence le vrai site.
 */
class NoIndexWhenStaging
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (config('jnuaf.noindex')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
