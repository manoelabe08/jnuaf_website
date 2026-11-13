<?php

namespace Tests\Feature;

use App\Models\Speaker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpeakersTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
        $this->admin = User::where('role', 'admin')->first();
    }

    private function create(array $overrides = []): Speaker
    {
        $this->actingAs($this->admin)->post(route('admin.speakers.store'), array_merge([
            'name' => 'Mariam Traoré',
            'session' => 'Atelier data',
            'bio' => "Ingénieure data depuis 10 ans.\nFondatrice d'une communauté de femmes dans la tech.",
            'position' => 0,
            'is_published' => '1',
            'photo' => UploadedFile::fake()->image('portrait.jpg', 2400, 1600),
        ], $overrides))->assertSessionHas('status');

        return Speaker::latest('id')->firstOrFail();
    }

    public function test_photo_is_cropped_square_and_resized(): void
    {
        $speaker = $this->create();

        Storage::disk('public')->assertExists($speaker->photo_path);
        $this->assertStringEndsWith('.jpg', $speaker->photo_path);

        [$width, $height] = getimagesizefromstring(Storage::disk('public')->get($speaker->photo_path));
        $this->assertSame([600, 600], [$width, $height]);
    }

    public function test_public_page_shows_photo_bio_excerpt_and_full_bio_dialog(): void
    {
        $speaker = $this->create();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString($speaker->photoUrl(), $html);
        $this->assertStringContainsString('Portrait de Mariam Traoré', $html);
        $this->assertStringContainsString('Lire la bio', $html);
        $this->assertStringContainsString('<dialog class="spk-dialog" id="spk-'.$speaker->id.'"', $html);
        // Retours à la ligne conservés dans la biographie complète.
        $this->assertStringContainsString('Ingénieure data depuis 10 ans.<br />', $html);
    }

    public function test_unpublished_speakers_and_their_bio_stay_hidden(): void
    {
        $this->create(['name' => 'Fiche Brouillon', 'bio' => 'Bio secrète en préparation.', 'is_published' => null]);

        $this->get('/')->assertDontSee('Fiche Brouillon')->assertDontSee('Bio secrète en préparation.');
    }

    public function test_speaker_without_bio_has_no_read_more_button(): void
    {
        $this->create(['bio' => null]);

        $this->get('/')->assertSee('Mariam Traoré')->assertDontSee('Lire la bio');
    }

    public function test_photo_can_be_replaced_or_removed(): void
    {
        $speaker = $this->create();
        $old = $speaker->photo_path;
        $fields = ['name' => $speaker->name, 'session' => $speaker->session, 'position' => 0, 'is_published' => '1'];

        $this->actingAs($this->admin)->put(route('admin.speakers.update', $speaker), $fields + ['photo' => UploadedFile::fake()->image('new.png', 800, 800)]);
        $speaker->refresh();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($speaker->photo_path);

        $this->actingAs($this->admin)->put(route('admin.speakers.update', $speaker), $fields + ['remove_photo' => '1']);
        $this->assertNull($speaker->fresh()->photo_path);
        $this->get('/')->assertSee('MT'); // initiales affichées à la place de la photo
    }

    public function test_non_image_files_are_rejected(): void
    {
        $this->actingAs($this->admin)->post(route('admin.speakers.store'), [
            'name' => 'X', 'session' => 'Y', 'position' => 0,
            'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');
    }
}
