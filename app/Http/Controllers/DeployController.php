<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * Remplace le terminal SSH sur un hébergement mutualisé : après l'envoi des fichiers (FTP),
 * un appel POST avec le jeton secret applique la mise à jour (clé, tables, caches).
 *
 *   curl -X POST -H "X-Deploy-Token: <DEPLOY_TOKEN>" https://test.jnuaf.org/_deploy
 *
 * Désactivé tant que DEPLOY_TOKEN n'est pas défini (au moins 32 caractères).
 */
class DeployController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = (string) config('jnuaf.deploy_token');

        // 404 plutôt que 403 : l'adresse n'existe pas tant que la fonction n'est pas activée.
        abort_if(strlen($token) < 32, 404);

        // Compteur d'échecs dans des fichiers : la table « cache » peut ne pas encore exister.
        $attempts = Cache::store('file');
        $key = 'deploy-failures:'.sha1((string) $request->ip());
        abort_if($attempts->get($key, 0) >= 5, 429, 'Trop de tentatives, réessayez dans 10 minutes.');

        if (! hash_equals($token, (string) $request->header('X-Deploy-Token'))) {
            $attempts->put($key, $attempts->get($key, 0) + 1, now()->addMinutes(10));
            abort(403);
        }

        @set_time_limit(300);

        $log = [];
        $run = function (string $command, array $parameters = []) use (&$log): bool {
            $code = Artisan::call($command, $parameters);
            $log[] = "> php artisan {$command} (code {$code})\n".trim(Artisan::output());

            return $code === 0;
        };

        $ok = true;

        // Première installation : la clé de chiffrement est écrite dans le fichier .env.
        if (blank(config('app.key'))) {
            $ok = $run('key:generate', ['--force' => true]);
        }

        $ok = $ok && $run('migrate', ['--force' => true]);
        // Le seeder est idempotent : territoires mis à jour, contenus et compte admin créés une seule fois.
        $ok = $ok && $run('db:seed', ['--force' => true]);

        if ($ok && ! file_exists(public_path('storage'))) {
            $ok = $run('storage:link');
        }

        $ok = $ok && $run('optimize:clear');
        $ok = $ok && $run('config:cache');
        $ok = $ok && $run('route:cache');
        $ok = $ok && $run('view:cache');

        $log[] = $ok ? 'Mise à jour terminée.' : 'ÉCHEC : voir la dernière commande ci-dessus.';

        return response(implode("\n\n", $log)."\n", $ok ? 200 : 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
