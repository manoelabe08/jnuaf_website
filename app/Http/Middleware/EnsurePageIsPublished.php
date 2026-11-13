<?php



namespace App\Http\Middleware;



use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;

use Symfony\Component\HttpFoundation\Response;



class EnsurePageIsPublished

{

    public function handle(Request $request, Closure $next, string $key): Response

    {

        try {

            $map = Cache::remember('public_pages_map', 60, fn () => DB::table('public_pages')->pluck('status', 'page_key')->all());

            $status = $map[$key] ?? 'published';

        } catch (\Throwable $e) {

            report($e);



            return $next($request);

        }



        if ($status !== 'published') {

            $user = $request->user();

            if (! ($user && $user->isAdmin())) {

                abort(404);

            }

            view()->share('pageDraft', true);

        }



        return $next($request);

    }

}

