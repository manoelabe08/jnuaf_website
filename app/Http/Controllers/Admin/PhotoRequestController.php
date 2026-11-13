<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Log;

use Illuminate\Validation\Rule;



class PhotoRequestController extends Controller

{

    public function store(Request $request): RedirectResponse

    {

        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([

            'scope' => ['required', Rule::in(['all', 'committee', 'cards', 'speakers', 'users'])],

            'mode' => ['required', Rule::in(['send', 'dry'])],

        ]);

        $args = ['--scope' => $data['scope'], '--limit' => 12];

        if ($data['mode'] === 'dry') {

            $args['--dry-run'] = true;

        }

        Artisan::call('photos:request', $args);

        $out = trim((string) preg_replace('/\s+/', ' ', Artisan::output()));

        Log::info('Demande de photos depuis le back-office', ['acteur_id' => $request->user()->id, 'mode' => $data['mode'], 'portee' => $data['scope']]);



        return back()->with('status', ($data['mode'] === 'dry' ? 'Simulation : ' : 'Envoi : ').mb_substr($out, 0, 600));

    }

}

