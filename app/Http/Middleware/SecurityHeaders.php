<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;



class SecurityHeaders

{




    
private function adminPolicy(Request $request): string

    {

        $images = "'self' data: blob:";

        if ($request->is('admin/messagerie*') || $request->is('admin/newsletter*')) {

            $images .= ' https:';

        }



        return "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src {$images}; font-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'";

    }



    public function handle(Request $request, Closure $next): Response


    {

        $response = $next($request);

        $headers = $response->headers;



        $headers->set('X-Content-Type-Options', 'nosniff');

        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');



        if (! $headers->has('X-Frame-Options')) {

            $headers->set('X-Frame-Options', 'SAMEORIGIN');

        }

        if (! $headers->has('Referrer-Policy')) {

            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        }

        if (str_starts_with((string) config('app.url'), 'https://')) {

            $headers->set('Strict-Transport-Security', 'max-age=15552000');

        }



        $mode = (string) config('jnuaf_security.csp_mode', 'report-only');

        if ($mode !== 'off' && ($request->is('admin') || $request->is('admin/*'))) {

            $name = $mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only';

            if (! $headers->has($name)) {

                $headers->set($name, $this->adminPolicy($request));

            }

        }



        return $response;

    }

}

