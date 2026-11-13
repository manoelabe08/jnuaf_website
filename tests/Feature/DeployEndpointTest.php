<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DeployEndpointTest extends TestCase
{
    private const TOKEN = 'jeton-de-test-suffisamment-long-0123456789';

    protected function setUp(): void
    {
        parent::setUp();
        // Le compteur d'échecs utilise le magasin « file » : en mémoire pendant les tests.
        config(['cache.stores.file' => ['driver' => 'array']]);
    }

    public function test_repeated_wrong_tokens_are_locked_out(): void
    {
        config(['jnuaf.deploy_token' => self::TOKEN]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/_deploy', [], ['X-Deploy-Token' => 'mauvais'])->assertForbidden();
        }
        // Même le bon jeton est refusé pendant le blocage.
        $this->post('/_deploy', [], ['X-Deploy-Token' => self::TOKEN])->assertStatus(429);
    }

    public function test_endpoint_is_disabled_without_token(): void
    {
        config(['jnuaf.deploy_token' => null]);

        $this->post('/_deploy', [], ['X-Deploy-Token' => 'nimporte'])->assertNotFound();
    }

    public function test_wrong_token_is_rejected_and_nothing_runs(): void
    {
        config(['jnuaf.deploy_token' => self::TOKEN]);
        Artisan::shouldReceive('call')->never();

        $this->post('/_deploy', [], ['X-Deploy-Token' => 'mauvais'])->assertForbidden();
        $this->post('/_deploy')->assertForbidden();
    }

    public function test_get_is_not_allowed(): void
    {
        config(['jnuaf.deploy_token' => self::TOKEN]);

        $this->get('/_deploy')->assertStatus(405);
    }

    public function test_valid_token_runs_migrations_and_caches(): void
    {
        config(['jnuaf.deploy_token' => self::TOKEN]);

        $called = [];
        Artisan::shouldReceive('call')->andReturnUsing(function ($command) use (&$called) {
            $called[] = $command;

            return 0;
        });
        Artisan::shouldReceive('output')->andReturn('ok');

        $this->post('/_deploy', [], ['X-Deploy-Token' => self::TOKEN])
            ->assertOk()
            ->assertSee('Mise à jour terminée.');

        $this->assertSame(['migrate', 'db:seed', 'optimize:clear', 'config:cache', 'route:cache', 'view:cache'],
            array_values(array_diff($called, ['storage:link'])));
    }

    public function test_failure_returns_500_and_stops(): void
    {
        config(['jnuaf.deploy_token' => self::TOKEN]);

        $called = [];
        Artisan::shouldReceive('call')->andReturnUsing(function ($command) use (&$called) {
            $called[] = $command;

            return $command === 'migrate' ? 1 : 0;
        });
        Artisan::shouldReceive('output')->andReturn('erreur SQL');

        $this->post('/_deploy', [], ['X-Deploy-Token' => self::TOKEN])
            ->assertStatus(500)
            ->assertSee('ÉCHEC');

        $this->assertSame(['migrate'], $called);
    }
}
