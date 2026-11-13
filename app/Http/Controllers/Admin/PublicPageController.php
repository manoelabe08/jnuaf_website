<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Services\SensitiveActionService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;

use Illuminate\View\View;



class PublicPageController extends Controller

{

    private const PATHS = [

        'home' => '/',

        'editions' => '/editions',

        'register' => '/inscription',

        'speakers' => '/intervenantes',

        'committee' => '/comite',

        'partners' => '/partenaires',

        'about' => '/a-propos',

        'faq' => '/faq',

        'legal' => '/legal',
        'team' => '/equipe',

    ];



    public function index(Request $request): View

    {

        return view('admin.pages.index', [

            'pages' => DB::table('public_pages')->orderBy('id')->get(),

            'paths' => self::PATHS,

            'isSuper' => $request->user()->isSuperAdmin(),

        ]);

    }



    public function toggle(Request $request, string $key): RedirectResponse

    {

        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);

        abort_if($key === 'home', 404);



        $page = DB::table('public_pages')->where('page_key', $key)->first();

        abort_unless($page, 404);



        $publish = $page->status !== 'published';

        DB::table('public_pages')->where('page_key', $key)->update([

            'status' => $publish ? 'published' : 'draft',

            'published_at' => $publish ? now() : null,

            'updated_by' => $user->id,

            'updated_at' => now(),

        ]);

        Cache::forget('public_pages_map');



        $this->record($request, $publish ? 'page publiee' : 'page masquee', ['Page' => (string) $page->label]);



        return back()->with('status', $publish ? 'Page publiée : '.$page->label.'.' : 'Page masquée : '.$page->label.'.');

    }



    public function home(Request $request): RedirectResponse

    {

        $user = $request->user();

        abort_unless($user->isSuperAdmin(), 403);



        $data = $request->validate(['variant' => ['required', 'in:old,new']]);



        if ($data['variant'] === 'new') {

            $missing = DB::table('public_pages')->where('page_key', '!=', 'home')->where('status', '!=', 'published')->pluck('label')->all();

            if ($missing) {

                return back()->with('status', 'Basculement refusé : publiez d\'abord ces pages : '.implode(', ', $missing).'.');

            }

        }



        DB::table('public_pages')->where('page_key', 'home')->update([

            'variant' => $data['variant'],

            'updated_by' => $user->id,

            'updated_at' => now(),

        ]);

        Cache::forget('public_home_variant');



        $this->record($request, 'accueil '.($data['variant'] === 'new' ? 'nouvelle version' : 'ancienne version'), ['Version' => $data['variant']]);



        return back()->with('status', $data['variant'] === 'new' ? 'La nouvelle page d\'accueil est active.' : 'L\'ancienne page d\'accueil est rétablie.');

    }



    private function record(Request $request, string $action, array $details): void

    {

        $svc = app(SensitiveActionService::class);

        $svc->log($request, $request->user(), 'Pages publiques : '.$action, $details);

        $svc->notify($request->user(), 'Pages publiques : '.$action, $details, $request);

    }

}

