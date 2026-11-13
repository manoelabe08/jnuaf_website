<?php

namespace Tests\Feature;

use App\Mail\PartnerInquiryReceived;
use App\Models\PartnerInquiry;
use App\Models\Territory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OrganisationAndPartnersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Mail::fake();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'organization' => 'Entreprise Exemple',
            'contact_name' => 'Fatou Diallo',
            'job_title' => 'Responsable RSE',
            'email' => 'Fatou@Exemple.sn',
            'phone' => '+221 77 000 00 00',
            'country' => 'Sénégal',
            'types' => ['sponsoring', 'jobs'],
            'message' => 'Nous souhaitons soutenir la Tournée du Numérique à Dakar.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_home_presents_the_organisation_before_the_event(): void
    {
        $html = $this->get('/')->assertOk()
            ->assertSee('#JNUAF.ORG')
            ->assertSee('Tournée du Numérique')
            ->assertSee('JNUAF Learn')
            ->assertSee('Boîte à CV')
            ->assertSee('Bureau exécutif / Direction')
            ->assertSee('Points focaux / délégations')
            ->assertSee('Suivi, Évaluation &amp; Reporting', false)
            ->assertSee('Insérer')
            ->assertSee('2021 – 2025')
            ->getContent();

        $this->assertLessThan(strpos($html, 'id="evenement"'), strpos($html, 'id="qui-sommes-nous"'));
    }

    public function test_focal_point_territories_are_available(): void
    {
        $this->assertEqualsCanonicalizing(
            ['ABJ', 'BYK', 'DKR', 'CAS', 'PAR', 'DIJ', 'ANG', 'BRE'],
            Territory::pluck('code')->all(),
        );
        $this->get('/')->assertSee('Brest')->assertSee('Angers')->assertSee('Dijon');
    }

    public function test_partner_can_send_an_inquiry(): void
    {
        $this->post(route('partners.store'), $this->payload())
            ->assertRedirect(route('home').'#partenaires')
            ->assertSessionHas('partner.organization', 'Entreprise Exemple');

        $inquiry = PartnerInquiry::sole();
        $this->assertSame('fatou@exemple.sn', $inquiry->email);
        $this->assertSame(['sponsoring', 'jobs'], $inquiry->types);
        Mail::assertSent(PartnerInquiryReceived::class, fn ($m) => $m->hasTo('fatou@exemple.sn'));
    }

    public function test_partner_inquiry_is_validated(): void
    {
        $this->post(route('partners.store'), $this->payload(['types' => ['inconnu'], 'message' => 'court', 'consent' => null]))
            ->assertSessionHasErrorsIn('partner', ['types.0', 'message', 'consent']);

        $this->post(route('partners.store'), $this->payload(['types' => []]))
            ->assertSessionHasErrorsIn('partner', ['types']);

        $this->assertDatabaseCount('partner_inquiries', 0);
    }

    public function test_only_admins_follow_up_partner_inquiries(): void
    {
        $this->post(route('partners.store'), $this->payload());
        $inquiry = PartnerInquiry::sole();
        $admin = User::factory()->admin()->create();
        $coordinator = User::factory()->coordinator(Territory::first())->create();

        $this->actingAs($coordinator)->get(route('admin.partners.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.partners.index'))->assertOk()->assertSee('Entreprise Exemple');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('1 nouvelle(s) demande(s) de partenariat');
        $this->actingAs($admin)->get(route('admin.partners.show', $inquiry))->assertOk()->assertSee('Sponsoring');

        $this->actingAs($admin)->patch(route('admin.partners.update', $inquiry), ['status' => 'discussion', 'notes' => 'RDV fixé'])
            ->assertSessionHas('status');
        $this->assertSame($admin->id, $inquiry->fresh()->handled_by);
    }
}
