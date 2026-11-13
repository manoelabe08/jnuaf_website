<?php



namespace App\Http\Controllers;



use App\Enums\Profile;

use App\Models\Speaker;

use App\Models\Territory;

use Illuminate\View\View;



class V2PageController extends Controller

{

    public function speakers(): View

    {

        return view('v2.speakers', [

            'speakers' => Speaker::where('is_published', true)->orderBy('position')->get(),

        ]);

    }



    public function register(): View

    {

        $territories = Territory::active()->get();



        return view('v2.register', [

            'territories' => $territories,

            'profiles' => Profile::cases(),

            'registrationsOpen' => (bool) config('jnuaf.registrations_open'),

            'fullTerritoryIds' => $territories->filter(fn (Territory $t) => $t->isFull())->pluck('id')->all(),

        ]);

    }

}

