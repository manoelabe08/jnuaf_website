<?php



namespace App\Http\Controllers;



use App\Enums\Profile;

use App\Models\GalleryPhoto;

use App\Models\ProgramItem;

use App\Models\Speaker;

use App\Models\Territory;

use App\Services\GalleryFolderService;

use Illuminate\Http\Request;

use Illuminate\View\View;



class V2Controller extends Controller

{

    public function home(Request $request): View

    {

        $territories = Territory::active()->get();

        $programs = $territories->mapWithKeys(fn (Territory $t) => [$t->code => ProgramItem::forTerritory($t->id)]);

        $photos = GalleryPhoto::where('is_published', true)->orderByDesc('edition')->orderBy('position')->get();



        return view('v2.home', [

            'user' => $request->user(),

            'territories' => $territories,

            'programs' => $programs,

            'speakers' => Speaker::where('is_published', true)->orderBy('position')->get(),

            'photos' => $photos,

            'galleryLots' => app(GalleryFolderService::class)->lots(),

            'profiles' => Profile::cases(),

            'registrationsOpen' => (bool) config('jnuaf.registrations_open'),

            'fullTerritoryIds' => $territories->filter(fn (Territory $t) => $t->isFull())->pluck('id')->all(),

        ]);

    }



    public function components(Request $request): View

    {

        return view('v2.components', ['user' => $request->user()]);

    }

}

