<?php
namespace Tests\Feature;

use App\Models\PartnerDocument;
use App\Models\PartnerShare;
use App\Models\User;
use App\Services\PartnerPortalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// EXCLUSIVEMENT sur une base de test : RefreshDatabase peut vider les tables.
class PartnerPortalSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function share(): array
    {
        $email = User::factory()->create()->email;
        $token = bin2hex(random_bytes(32));
        $share = PartnerShare::create([
            'name' => 'Partenaire de test', 'email' => mb_strtolower($email),
            'token_hash' => hash('sha256', $token), 'generation' => 1,
            'status' => 'active', 'expires_at' => now()->addDay(),
        ]);
        return [$share, $token];
    }

    private function issuedCode(PartnerShare $share): string
    {
        $portal = app(PartnerPortalService::class);
        $portal->requestCode($share->fresh(), $share->email, '127.0.0.1');
        $portal->processMaintenance();
        $notice = DB::table('partner_notices')->where('context_share_id', $share->id)->latest('id')->first();
        preg_match('/code JNUAF : (\d{6})/', Crypt::decryptString($notice->body), $matches);
        return $matches[1];
    }

    public function test_code_is_single_use_and_creates_no_admin_login(): void
    {
        Mail::fake();
        [$share, $token] = $this->share();
        $code = $this->issuedCode($share);
        $this->withSession(['partner_portal' => ['email' => [$share->id => $share->email]]])
            ->post(route('partner-portal.verify', $token), ['code' => $code])
            ->assertRedirect(route('partner-portal.documents', $token));
        $this->assertGuest();
        $this->assertFalse(app(PartnerPortalService::class)->verifyCode($share->fresh(), $share->email, $code));
    }

    public function test_three_wrong_codes_revoke_the_link(): void
    {
        Mail::fake();
        [$share] = $this->share();
        $correct = $this->issuedCode($share);
        $wrong = $correct === '111111' ? '222222' : '111111';
        $portal = app(PartnerPortalService::class);
        for ($i = 0; $i < 3; $i++) { $portal->verifyCode($share->fresh(), $share->email, $wrong); }
        $this->assertSame('revoked', $share->fresh()->status);
        $this->assertSame(3, $share->fresh()->failed_attempts);
    }

    public function test_an_existing_but_unauthorized_email_receives_no_code(): void
    {
        Mail::fake();
        [$share] = $this->share();
        $other = User::factory()->create()->email;
        app(PartnerPortalService::class)->requestCode($share, $other, '127.0.0.2');
        $this->assertSame(0, $share->fresh()->code_count);
        $this->assertDatabaseCount('partner_notices', 0);
    }

    public function test_second_code_waits_until_expiry_plus_90_seconds(): void
    {
        Mail::fake();
        [$share] = $this->share();
        $this->issuedCode($share);
        $portal = app(PartnerPortalService::class);
        $this->travel(389)->seconds();
        $portal->requestCode($share->fresh(), $share->email, '127.0.0.1');
        $this->assertSame(1, $share->fresh()->code_count);
        $this->travel(2)->seconds();
        $portal->requestCode($share->fresh(), $share->email, '127.0.0.1');
        $this->assertSame(2, $share->fresh()->code_count);
    }

    public function test_third_expired_code_revokes_without_another_page_visit(): void
    {
        Mail::fake();
        [$share] = $this->share();
        $this->issuedCode($share);
        $this->travel(391)->seconds();
        $this->issuedCode($share->fresh());
        $this->travel(451)->seconds();
        $this->issuedCode($share->fresh());
        $this->travel(301)->seconds();
        app(PartnerPortalService::class)->processMaintenance();
        $this->assertSame('revoked', $share->fresh()->status);
    }

    public function test_another_partners_document_is_not_accessible(): void
    {
        [$share, $token] = $this->share();
        $document = PartnerDocument::create([
            'title' => 'Non partagé', 'stored_name' => bin2hex(random_bytes(24)).'.pdf',
            'download_name' => 'secret.pdf', 'bytes' => 20, 'active' => true,
        ]);
        $this->withSession(['partner_portal' => ['auth' => [$share->id => [
            'generation' => 1, 'email' => $share->email, 'issued_at' => time(), 'last_activity' => time(),
        ]]]])->get(route('partner-portal.file', [$token, $document->id, 'lire']))->assertNotFound();
    }

    public function test_storage_inside_webroot_is_refused(): void
    {
        config(['partner_portal.private_root' => public_path(), 'partner_portal.web_root' => public_path()]);
        $this->expectException(\RuntimeException::class);
        app(PartnerPortalService::class)->root();
    }

    public function test_old_token_no_longer_opens_after_renewal(): void
    {
        Mail::fake();
        [$share, $token] = $this->share();
        $base = sys_get_temp_dir().'/jnuaf-test-'.bin2hex(random_bytes(8));
        mkdir($base); mkdir($base.'/web'); mkdir($base.'/private');
        config([
            'partner_portal.private_root' => $base.'/private', 'partner_portal.web_root' => $base.'/web',
            'partner_portal.admin_emails' => ['admin@example.test'],
            'partner_portal.contact_email' => 'comite@example.test',
        ]);
        try { app(PartnerPortalService::class)->sendInvitation($share); }
        finally { rmdir($base.'/private'); rmdir($base.'/web'); rmdir($base); }
        $this->get(route('partner-portal.landing', $token))->assertStatus(410);
    }
}
