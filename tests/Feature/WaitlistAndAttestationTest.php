<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Mail\AttestationAvailable;
use App\Mail\RegistrationConfirmed;
use App\Mail\RegistrationWaitlisted;
use App\Models\Registration;
use App\Models\Territory;
use App\Models\User;
use App\Services\BadgeService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WaitlistAndAttestationTest extends TestCase
{
    use RefreshDatabase;

    private Territory $abj;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
        $this->abj = Territory::where('code', 'ABJ')->first();
        $this->abj->update(['capacity' => 1]);
    }

    private function register(string $email): array
    {
        return app(RegistrationService::class)->register([
            'profile' => 'participante', 'last_name' => 'Test', 'first_name' => 'Aya', 'email' => $email,
            'phone' => '+225 07 00 00 00 00', 'territory_id' => $this->abj->id, 'city' => 'Cocody', 'consent_data' => 1,
        ]);
    }

    public function test_registrations_beyond_capacity_go_to_waitlist_without_badge(): void
    {
        [$first] = $this->register('a@ex.ci');
        [$second, $token] = $this->register('b@ex.ci');

        $this->assertTrue($first->isConfirmed());
        $this->assertTrue($second->isWaitlisted());
        Mail::assertSent(RegistrationWaitlisted::class, fn ($m) => $m->hasTo('b@ex.ci'));
        Mail::assertNotSent(RegistrationConfirmed::class, fn ($m) => $m->hasTo('b@ex.ci'));

        // Pas de badge tant qu'aucune place n'est attribuée.
        $this->get(route('registration.badge', $token))->assertStatus(410);
        $this->get(route('registration.manage', $token))->assertSee("liste d'attente", false);
    }

    public function test_full_territory_is_flagged_in_form(): void
    {
        $this->register('a@ex.ci');

        $this->get('/')->assertSee('complet, liste d&#039;attente', false);
    }

    public function test_cancelling_a_confirmed_registration_promotes_the_first_waitlisted(): void
    {
        [, $firstToken] = $this->register('a@ex.ci');
        [$second] = $this->register('b@ex.ci');
        [$third] = $this->register('c@ex.ci');

        $this->post(route('registration.cancel', $firstToken));

        $this->assertTrue($second->fresh()->isConfirmed());
        $this->assertTrue($third->fresh()->isWaitlisted());
        Mail::assertSent(RegistrationConfirmed::class, fn ($m) => $m->hasTo('b@ex.ci') && $m->promoted);
    }

    public function test_deleting_data_or_admin_cancellation_also_frees_the_seat(): void
    {
        [$first, $firstToken] = $this->register('a@ex.ci');
        [$second] = $this->register('b@ex.ci');
        [$third] = $this->register('c@ex.ci');

        $this->delete(route('registration.destroy', $firstToken));
        $this->assertTrue($second->fresh()->isConfirmed());

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->patch(route('admin.registrations.update', $second), ['status' => 'cancelled']);
        $this->assertTrue($third->fresh()->isConfirmed());
    }

    public function test_waitlisted_badge_is_refused_at_checkin(): void
    {
        $this->register('a@ex.ci');
        [$second] = $this->register('b@ex.ci');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson(route('admin.checkin.store'), ['code' => $second->badge_code])
            ->assertStatus(422)->assertJson(['state' => 'invalid']);
    }

    public function test_attestation_only_for_checked_in_participants(): void
    {
        [$registration, $token] = $this->register('a@ex.ci');

        $this->get(route('registration.attestation', $token))->assertForbidden();

        $registration->forceFill(['checked_in_at' => now()])->save();

        $this->get(route('registration.attestation', $token))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', app(BadgeService::class)->attestationPdf($registration));
    }

    public function test_attestations_are_sent_once_to_present_people(): void
    {
        Territory::where('code', 'ABJ')->update(['capacity' => null]);
        [$present] = $this->register('a@ex.ci');
        $this->register('b@ex.ci');
        $present->forceFill(['checked_in_at' => now()])->save();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.attestations.send'))->assertSessionHas('status');
        $this->artisan('jnuaf:send-attestations')->assertSuccessful();

        Mail::assertSent(AttestationAvailable::class, 1);
        Mail::assertSent(AttestationAvailable::class, fn ($m) => $m->hasTo('a@ex.ci'));
    }

    public function test_admin_can_confirm_a_waitlisted_registration_manually(): void
    {
        $this->register('a@ex.ci');
        [$second] = $this->register('b@ex.ci');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->patch(route('admin.registrations.update', $second), ['status' => 'confirmed']);

        $this->assertSame(RegistrationStatus::Confirmed, $second->fresh()->status);
        Mail::assertSent(RegistrationConfirmed::class, fn ($m) => $m->hasTo('b@ex.ci'));
    }
}
