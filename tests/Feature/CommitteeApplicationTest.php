<?php

namespace Tests\Feature;

use App\Mail\CommitteeApplicationReceived;
use App\Models\CommitteeApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommitteeApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
    }

    private function pdf(string $name, int $kb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kb, 'application/pdf');
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Aya Kouassi',
            'email' => 'aya@exemple.ci',
            'phone' => '+225 07 77 20 08 73',
            'country' => "Côte d'Ivoire",
            'pole' => 'Communication & Médias',
            'cv' => $this->pdf('cv.pdf'),
            'letter' => $this->pdf('lettre.pdf'),
            'consent' => '1',
        ], $overrides);
    }

    public function test_candidate_can_apply_with_pdf_documents(): void
    {
        $this->post('/comite/candidature', $this->payload())
            ->assertRedirect(route('home').'#candidature')
            ->assertSessionHas('committee.pole', 'Communication & Médias');

        $application = CommitteeApplication::sole();
        Storage::disk('local')->assertExists([$application->cv_path, $application->letter_path]);
        Mail::assertSent(CommitteeApplicationReceived::class, fn ($m) => $m->hasTo('aya@exemple.ci'));
    }

    public function test_documents_must_be_pdf_under_two_megabytes(): void
    {
        $this->post('/comite/candidature', $this->payload([
            'cv' => UploadedFile::fake()->image('cv.png'),
            'letter' => $this->pdf('lettre.pdf', 3000),
        ]))->assertSessionHasErrorsIn('committee', ['cv', 'letter']);

        $this->assertDatabaseCount('committee_applications', 0);
    }

    public function test_unknown_pole_is_rejected(): void
    {
        $this->post('/comite/candidature', $this->payload(['pole' => 'Pôle inventé']))
            ->assertSessionHasErrorsIn('committee', ['pole']);
    }

    public function test_admin_can_download_and_delete_application_documents(): void
    {
        $this->post('/comite/candidature', $this->payload());
        $application = CommitteeApplication::sole();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.committee.download', [$application, 'cv']))->assertOk();

        $this->actingAs($admin)->patch(route('admin.committee.update', $application), ['status' => 'accepted', 'notes' => 'Profil solide'])
            ->assertSessionHas('status');
        $this->assertSame($admin->id, $application->fresh()->reviewed_by);

        $this->actingAs($admin)->delete(route('admin.committee.destroy', $application));
        Storage::disk('local')->assertMissing($application->cv_path);
        $this->assertDatabaseCount('committee_applications', 0);
    }
}
