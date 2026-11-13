<?php



namespace App\Http\Controllers;



use App\Enums\Profile;

use App\Models\GalleryPhoto;

use App\Models\ProgramItem;

use App\Models\Speaker;

use App\Models\Territory;

use App\Services\GalleryFolderService;

use Illuminate\Http\Response;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;

use Illuminate\View\View;



class HomeController extends Controller

{

    public function __invoke(): Response|View

    {

        $data = $this->data();



        if ($this->variant() === 'new') {

            try {

                return response(view('v2.home', $data)->render());

            } catch (\Throwable $e) {

                report($e);

            }

        }



        return view('home', $data);

    }



    public function old(): View

    {

        return view('home', $this->data());

    }



    private function variant(): string

    {

        try {

            return Cache::remember('public_home_variant', 60, fn () => (string) (DB::table('public_pages')->where('page_key', 'home')->value('variant') ?: 'old'));

        } catch (\Throwable $e) {

            report($e);



            return 'old';

        }

    }



    private function data(): array

    {

        $territories = Territory::active()->get();



        // Un programme par territoire (programme propre ou, à défaut, programme type).

        $programs = $territories->mapWithKeys(fn (Territory $t) => [$t->code => ProgramItem::forTerritory($t->id)]);



        $photos = GalleryPhoto::where('is_published', true)

            ->orderByDesc('edition')->orderBy('position')->get();



        return [

            'territories' => $territories,

            'programs' => $programs,

            'speakers' => Speaker::where('is_published', true)->orderBy('position')->get(),

            'photos' => $photos,

            'galleryLots' => app(GalleryFolderService::class)->lots(),

            'profiles' => Profile::cases(),

            'registrationsOpen' => (bool) config('jnuaf.registrations_open'),

            // Territoires complets : signalés dans le formulaire (l'inscription passe en liste d'attente).

            'fullTerritoryIds' => $territories->filter(fn (Territory $t) => $t->isFull())->pluck('id')->all(),

        ];

    }

}

