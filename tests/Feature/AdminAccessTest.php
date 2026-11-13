<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Mail\EventReminder;
use App\Models\ExportLog;
use App\Models\Registration;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private Territory $abj;

    private Territory $dkr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->abj = Territory::where('code', 'ABJ')->first();
        $this->dkr = Territory::where('code', 'DKR')->first();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/inscriptions/export')->assertRedirect(route('login'));
    }

    public function test_login_and_logout(): void
    {
        $user = User::factory()->admin()->create(['email' => 'bp@jnuaf.org']);

        $this->post(route('login'), ['email' => 'bp@jnuaf.org', 'password' => 'mauvais'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login'), ['email' => 'bp@jnuaf.org', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertSee('Tableau de bord');

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_coordinator_only_sees_own_territory(): void
    {
        $mine = Registration::factory()->create(['territory_id' => $this->abj->id, 'last_name' => 'Abidjanaise']);
        $other = Registration::factory()->create(['territory_id' => $this->dkr->id, 'last_name' => 'Dakaroise']);
        $coordinator = User::factory()->coordinator($this->abj)->create();

        $this->actingAs($coordinator)->get(route('admin.registrations.index'))
            ->assertOk()->assertSee('Abidjanaise')->assertDontSee('Dakaroise');
        $this->actingAs($coordinator)->get(route('admin.registrations.show', $mine))->assertOk();
        $this->actingAs($coordinator)->get(route('admin.registrations.show', $other))->assertForbidden();
    }

    public function test_coordinator_cannot_reach_pilot_office_pages(): void
    {
        $coordinator = User::factory()->coordinator($this->abj)->create();

        foreach (['admin.committee.index', 'admin.program.index', 'admin.gallery.index', 'admin.users.index', 'admin.speakers.index'] as $route) {
            $this->actingAs($coordinator)->get(route($route))->assertForbidden();
        }
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        Registration::factory()->create(['territory_id' => $this->abj->id]);

        foreach (['admin.dashboard', 'admin.registrations.index', 'admin.checkin.index', 'admin.committee.index', 'admin.program.index', 'admin.gallery.index', 'admin.users.index', 'admin.speakers.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_export_is_scoped_and_logged(): void
    {
        Registration::factory()->create(['territory_id' => $this->abj->id, 'last_name' => 'Abidjanaise']);
        Registration::factory()->create(['territory_id' => $this->dkr->id, 'last_name' => 'Dakaroise']);
        $coordinator = User::factory()->coordinator($this->abj)->create();

        $csv = $this->actingAs($coordinator)->get(route('admin.registrations.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('Abidjanaise', $csv);
        $this->assertStringNotContainsString('Dakaroise', $csv);
        $log = ExportLog::sole();
        $this->assertSame($coordinator->id, $log->user_id);
        $this->assertSame(1, $log->row_count);
    }

    public function test_checkin_flow(): void
    {
        $registration = Registration::factory()->create(['territory_id' => $this->abj->id]);
        $coordinator = User::factory()->coordinator($this->abj)->create();

        // Le QR encode l'URL de pointage complète.
        $qrContent = route('admin.checkin.token', $registration->qr_token);

        $this->actingAs($coordinator)->postJson(route('admin.checkin.store'), ['code' => $qrContent])
            ->assertOk()->assertJson(['state' => 'ok']);
        $this->assertNotNull($registration->fresh()->checked_in_at);
        $this->assertSame($coordinator->id, $registration->fresh()->checked_in_by);

        $this->actingAs($coordinator)->postJson(route('admin.checkin.store'), ['code' => $registration->badge_code])
            ->assertStatus(422)->assertJson(['state' => 'already']);
    }

    public function test_checkin_rejects_cancelled_unknown_and_foreign_badges(): void
    {
        $cancelled = Registration::factory()->create(['territory_id' => $this->abj->id, 'status' => RegistrationStatus::Cancelled]);
        $foreign = Registration::factory()->create(['territory_id' => $this->dkr->id]);
        $coordinator = User::factory()->coordinator($this->abj)->create();

        $this->actingAs($coordinator)->postJson(route('admin.checkin.store'), ['code' => $cancelled->badge_code])
            ->assertJson(['state' => 'invalid']);
        $this->actingAs($coordinator)->postJson(route('admin.checkin.store'), ['code' => $foreign->qr_token])
            ->assertJson(['state' => 'invalid']);
        $this->actingAs($coordinator)->postJson(route('admin.checkin.store'), ['code' => 'INCONNU'])
            ->assertJson(['state' => 'unknown']);

        $this->assertNull($foreign->fresh()->checked_in_at);
    }

    public function test_scanning_qr_link_shows_badge_before_validation(): void
    {
        $registration = Registration::factory()->create(['territory_id' => $this->abj->id]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.checkin.token', $registration->qr_token))
            ->assertOk()->assertSee($registration->badge_code)->assertSee("Valider l'entrée", false);
        $this->assertNull($registration->fresh()->checked_in_at);
    }

    public function test_reminders_are_sent_once_to_confirmed_registrations(): void
    {
        Mail::fake();
        Registration::factory()->count(2)->create(['territory_id' => $this->abj->id]);
        Registration::factory()->create(['territory_id' => $this->abj->id, 'status' => RegistrationStatus::Cancelled]);

        $this->artisan('jnuaf:send-reminders', ['--force' => true])->assertSuccessful();
        $this->artisan('jnuaf:send-reminders', ['--force' => true])->assertSuccessful();

        Mail::assertSent(EventReminder::class, 2);
    }

    public function test_admin_manages_content_shown_on_home(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.speakers.store'), [
            'name' => 'Mariam Traoré', 'session' => 'Atelier data', 'position' => 0, 'is_published' => '1',
        ])->assertSessionHas('status');

        $this->actingAs($admin)->post(route('admin.program.store'), [
            'territory_id' => $this->dkr->id, 'slot' => '9h00', 'title' => 'Hackathon dakarois', 'kind' => 'Atelier', 'position' => 0,
        ])->assertSessionHas('status');

        $this->get('/')->assertSee('Mariam Traoré')->assertSee('Hackathon dakarois');
    }
}
