<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Mail\AdminAlert;
use App\Models\CommitteeApplication;
use App\Models\ExportLog;
use App\Models\PartnerInquiry;
use App\Models\Registration;
use App\Models\Territory;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdministrationToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('role', 'admin')->first();
    }

    /* ---------- Paramètres ---------- */

    public function test_settings_change_site_behaviour_and_legal_texts(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'event_date' => '2027-04-10',
            'retention_months' => 18,
            'committee_retention_months' => 3,
            'notify_email' => 'bureau@jnuaf.org',
            'legal_controller' => 'Association JNUAF, Abidjan',
            'legal_host' => 'Hébergeur Exemple SAS',
            // registrations_open absent = case décochée
        ])->assertSessionHas('status');

        $this->assertSame('2027-04-10', config('jnuaf.event_date'));
        $this->assertFalse(config('jnuaf.registrations_open'));

        $this->get('/')
            ->assertSee('Les inscriptions sont fermées.')
            ->assertSee('Association JNUAF, Abidjan')
            ->assertSee('Hébergeur Exemple SAS')
            ->assertSee('anonymisées 18 mois');

        $this->post('/inscription', [])->assertForbidden();
    }

    public function test_settings_are_admin_only_and_validated(): void
    {
        $coordinator = User::factory()->coordinator(Territory::first())->create();
        $this->actingAs($coordinator)->get(route('admin.settings.edit'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($this->admin)->put(route('admin.settings.update'), ['event_date' => 'demain', 'retention_months' => 0, 'committee_retention_months' => 1])
            ->assertSessionHasErrors(['event_date', 'retention_months']);
    }

    /* ---------- Conservation des données ---------- */

    public function test_purge_anonymizes_old_registrations_and_deletes_old_applications(): void
    {
        Storage::fake('local');
        config(['jnuaf.event_date' => now()->subMonths(13)->toDateString(), 'jnuaf.privacy.retention_months' => 12]);
        $territory = Territory::first();

        $current = Registration::factory()->create(['territory_id' => $territory->id, 'last_name' => 'Kouassi', 'checked_in_at' => now()->subMonths(13)]);
        $recentOther = Registration::factory()->create(['territory_id' => $territory->id, 'edition' => '2026', 'last_name' => 'Recente']);

        $decided = CommitteeApplication::create(['full_name' => 'Ancienne', 'email' => 'a@ex.ci', 'phone' => '+225 0700000000', 'country' => 'France', 'pole' => 'Programmes',
            'cv_path' => UploadedFile::fake()->create('cv.pdf', 10)->store('committee', 'local'),
            'letter_path' => UploadedFile::fake()->create('l.pdf', 10)->store('committee', 'local'), 'consented_at' => now()]);
        $decided->forceFill(['status' => ApplicationStatus::Rejected, 'reviewed_at' => now()->subMonths(7)])->save();
        $pending = CommitteeApplication::create(['full_name' => 'En cours', 'email' => 'b@ex.ci', 'phone' => '+225 0700000000', 'country' => 'France', 'pole' => 'Programmes',
            'cv_path' => 'committee/x.pdf', 'letter_path' => 'committee/y.pdf', 'consented_at' => now()]);

        $this->artisan('jnuaf:purge-data', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('Kouassi', $current->fresh()->last_name);

        $this->artisan('jnuaf:purge-data')->assertSuccessful();

        $current->refresh();
        $this->assertSame('Anonyme', $current->last_name);
        $this->assertStringEndsWith('@anonymise.invalid', $current->email);
        $this->assertNotNull($current->anonymized_at);
        $this->assertNotNull($current->checked_in_at, 'La présence est conservée pour les statistiques.');
        $this->assertSame('Recente', $recentOther->fresh()->last_name);

        $this->assertModelMissing($decided);
        Storage::disk('local')->assertMissing($decided->cv_path);
        $this->assertModelExists($pending);
    }

    public function test_purge_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('jnuaf:purge-data')->assertSuccessful();
    }

    /* ---------- Mot de passe oublié ---------- */

    public function test_password_reset_flow(): void
    {
        Notification::fake();

        $this->get(route('login'))->assertSee('Mot de passe oublié ?');
        $this->post(route('password.email'), ['email' => $this->admin->email])->assertSessionHas('status');
        $this->post(route('password.email'), ['email' => 'inconnu@jnuaf.org'])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($this->admin, ResetPasswordNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $this->admin->email]))->assertOk();
        $this->post(route('password.update'), [
            'token' => $token, 'email' => $this->admin->email,
            'password' => 'NouveauMotDePasse2027', 'password_confirmation' => 'NouveauMotDePasse2027',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NouveauMotDePasse2027', $this->admin->fresh()->password));
    }

    public function test_invalid_reset_token_is_rejected(): void
    {
        $this->post(route('password.update'), [
            'token' => 'faux', 'email' => $this->admin->email,
            'password' => 'NouveauMotDePasse2027', 'password_confirmation' => 'NouveauMotDePasse2027',
        ])->assertSessionHasErrors('email');
    }

    /* ---------- Alertes au Bureau ---------- */

    public function test_bureau_is_alerted_of_new_applications_and_inquiries(): void
    {
        Mail::fake();
        Storage::fake('local');
        config(['jnuaf.notify_email' => 'bureau@jnuaf.org']);

        $this->post('/comite/candidature', [
            'full_name' => 'Aya Kouassi', 'email' => 'aya@ex.ci', 'phone' => '+225 07 77 20 08 73', 'country' => "Côte d'Ivoire",
            'pole' => 'Programmes', 'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf'),
            'letter' => UploadedFile::fake()->create('l.pdf', 50, 'application/pdf'), 'consent' => '1',
        ]);
        $this->post(route('partners.store'), [
            'organization' => 'Entreprise Exemple', 'contact_name' => 'Fatou Diallo', 'email' => 'f@ex.sn', 'country' => 'Sénégal',
            'types' => ['sponsoring'], 'message' => 'Nous souhaitons soutenir la Tournée.', 'consent' => '1',
        ]);

        Mail::assertSent(AdminAlert::class, 2);
        Mail::assertSent(AdminAlert::class, fn ($m) => $m->hasTo($this->admin->email) && $m->hasTo('bureau@jnuaf.org') && str_contains($m->title, 'comité'));
        Mail::assertSent(AdminAlert::class, fn ($m) => str_contains($m->title, 'partenariat'));
    }

    /* ---------- Territoires ---------- */

    public function test_admin_manages_territories(): void
    {
        $this->actingAs($this->admin)->post(route('admin.territories.store'), [
            'code' => 'lyo', 'city' => 'Lyon', 'country' => 'France', 'capacity' => 120, 'position' => 9, 'is_active' => '1',
        ])->assertSessionHas('status');

        $lyon = Territory::where('code', 'LYO')->firstOrFail();
        $this->assertSame(120, $lyon->capacity);
        $this->get('/')->assertSee('Lyon');

        $this->actingAs($this->admin)->put(route('admin.territories.update', $lyon), [
            'code' => 'LYO', 'city' => 'Lyon', 'country' => 'France', 'position' => 9,
        ]);
        $this->assertFalse($lyon->fresh()->is_active);
        $this->get('/')->assertDontSee('Lyon (France)');

        $this->actingAs($this->admin)->post(route('admin.territories.store'), ['code' => 'ABJ', 'city' => 'X', 'country' => 'Y', 'position' => 0])
            ->assertSessionHasErrors('code');
    }

    public function test_territory_with_registrations_cannot_be_deleted(): void
    {
        $territory = Territory::first();
        Registration::factory()->create(['territory_id' => $territory->id]);

        $this->actingAs($this->admin)->delete(route('admin.territories.destroy', $territory))->assertSessionHasErrors('territory');
        $this->assertModelExists($territory);
    }

    /* ---------- Exports ---------- */

    public function test_committee_and_partner_exports_are_logged(): void
    {
        CommitteeApplication::create(['full_name' => 'Aya Candidate', 'email' => 'a@ex.ci', 'phone' => '+225 0700000000', 'country' => 'France', 'pole' => 'Programmes', 'cv_path' => 'c', 'letter_path' => 'l', 'consented_at' => now()]);
        PartnerInquiry::create(['organization' => 'Entreprise Exemple', 'contact_name' => 'Fatou', 'email' => 'f@ex.sn', 'country' => 'Sénégal', 'types' => ['jobs'], 'message' => 'Offres de stage', 'consented_at' => now()]);

        $csv = $this->actingAs($this->admin)->get(route('admin.committee.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Aya Candidate', $csv);

        $csv = $this->actingAs($this->admin)->get(route('admin.partners.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString('Entreprise Exemple', $csv);
        $this->assertStringContainsString('Boîte à CV', $csv);

        $this->assertEqualsCanonicalizing(['candidatures-comite', 'demandes-partenaires'], ExportLog::pluck('type')->all());
    }

    /* ---------- Logo dans l'onglet du navigateur ---------- */

    public function test_logo_icon_is_declared_on_site_admin_and_login(): void
    {
        foreach (['/', route('login')] as $url) {
            $this->get($url)->assertSee('favicon.svg')->assertSee('apple-touch-icon.png');
        }
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertSee('favicon.svg')->assertSee('favicon.ico');

        // favicon.ico valide (en-tête ICO), plus le fichier vide d'origine.
        $this->assertSame("\x00\x00\x01\x00", substr(file_get_contents(public_path('favicon.ico')), 0, 4));
        $this->assertSame([180, 180], array_slice(getimagesize(public_path('apple-touch-icon.png')), 0, 2));
    }

    /* ---------- Site : polices, partage ---------- */

    public function test_site_uses_self_hosted_fonts_and_sharing_metadata(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringContainsString('fonts/fonts.css', $html);
        $this->assertStringContainsString('property="og:image"', $html);
        $this->assertFileExists(public_path('images/partage.jpg'));
        $this->assertFileExists(public_path('favicon.svg'));

        preg_match_all('/url\(([^)]+\.woff2)\)/', file_get_contents(public_path('fonts/fonts.css')), $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $file) {
            $this->assertFileExists(public_path('fonts/'.$file));
        }
    }
}
