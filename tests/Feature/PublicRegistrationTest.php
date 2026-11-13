<?php

namespace Tests\Feature;

use App\Enums\RegistrationStatus;
use App\Mail\RegistrationConfirmed;
use App\Models\Registration;
use App\Models\Territory;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private Territory $abidjan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->abidjan = Territory::where('code', 'ABJ')->first();
        Mail::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'profile' => 'participante',
            'last_name' => 'Kouassi',
            'first_name' => 'Aya',
            'email' => 'Aya.Kouassi@exemple.ci',
            'phone' => '+225 07 00 00 00 00',
            'territory_id' => $this->abidjan->id,
            'city' => 'Cocody',
            'organization' => 'UVCI',
            'motivation' => 'Rencontrer des mentors.',
            'consent_data' => '1',
        ], $overrides);
    }

    public function test_home_page_renders_dynamic_content(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Réservez')
            ->assertSee('Casablanca')
            ->assertSee("Mot du Bureau de Pilotage et de la coordination d'Abidjan")
            ->assertSee('Rejoignez');
    }

    public function test_participant_can_register_and_receives_badge_by_email(): void
    {
        $this->post('/inscription', $this->payload())
            ->assertRedirect(route('home').'#inscription')
            ->assertSessionHas('registration.first_name', 'Aya');

        $registration = Registration::sole();
        $this->assertSame('aya.kouassi@exemple.ci', $registration->email);
        $this->assertMatchesRegularExpression('/^JNUAF27-ABJ-[A-Z0-9]{6}$/', $registration->badge_code);
        $this->assertSame(RegistrationStatus::Confirmed, $registration->status);
        $this->assertNotNull($registration->badge_sent_at);
        $this->assertFalse($registration->consent_image);

        Mail::assertSent(RegistrationConfirmed::class, function (RegistrationConfirmed $mail) use ($registration) {
            return $mail->hasTo('aya.kouassi@exemple.ci')
                && $mail->registration->is($registration)
                && Registration::findByManageToken($mail->manageToken)?->is($registration);
        });
    }

    public function test_badge_pdf_attachment_is_generated(): void
    {
        [$registration] = app(RegistrationService::class)->register($this->payload());

        $attachment = (new RegistrationConfirmed($registration, 'x'))->attachments()[0];
        $this->assertSame("badge-{$registration->badge_code}.pdf", $attachment->as);

        $this->assertStringStartsWith('%PDF', app(\App\Services\BadgeService::class)->pdf($registration));
    }

    public function test_same_email_cannot_register_twice_for_the_edition(): void
    {
        $this->post('/inscription', $this->payload());
        $this->post('/inscription', $this->payload(['email' => 'aya.kouassi@exemple.ci']))
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Registration::count());
    }

    public function test_required_fields_and_consent_are_validated(): void
    {
        $this->post('/inscription', $this->payload(['last_name' => '', 'phone' => 'abc', 'consent_data' => null]))
            ->assertRedirect(route('home').'#inscription')
            ->assertSessionHasErrors(['last_name', 'phone', 'consent_data']);
    }

    public function test_speaker_profiles_require_expertise_and_irrelevant_fields_are_dropped(): void
    {
        $this->post('/inscription', $this->payload(['profile' => 'intervenante']))
            ->assertSessionHasErrors(['expertise', 'bio', 'topic']);

        $this->post('/inscription', $this->payload([
            'profile' => 'mentor', 'expertise' => 'Cybersécurité', 'bio' => 'Ingénieure sécurité.',
        ]))->assertSessionHasNoErrors();

        $mentor = Registration::sole();
        $this->assertSame('Cybersécurité', $mentor->expertise);
        // Les champs d'un autre profil envoyés par le navigateur ne sont pas conservés.
        $this->assertNull($mentor->organization);
        $this->assertNull($mentor->motivation);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $this->post('/inscription', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');
        $this->assertSame(0, Registration::count());
    }

    public function test_personal_link_allows_update_cancel_and_erasure(): void
    {
        [$registration, $token] = app(RegistrationService::class)->register($this->payload());

        $this->get(route('registration.manage', $token))->assertOk()->assertSee($registration->badge_code);

        $this->get(route('registration.badge', $token))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->patch(route('registration.update', $token), ['phone' => '+225 01 02 03 04 05', 'city' => 'Plateau', 'consent_image' => '1'])
            ->assertSessionHas('status');
        $registration->refresh();
        $this->assertSame('Plateau', $registration->city);
        $this->assertTrue($registration->consent_image);

        $this->post(route('registration.cancel', $token))->assertSessionHas('status');
        $this->assertTrue($registration->fresh()->isCancelled());
        $this->get(route('registration.badge', $token))->assertStatus(410);

        $this->delete(route('registration.destroy', $token))->assertRedirect(route('home'));
        $this->assertDatabaseCount('registrations', 0);
    }

    public function test_unknown_personal_link_returns_404(): void
    {
        $this->get('/mon-inscription/'.str_repeat('a', 48))->assertNotFound();
    }
}
