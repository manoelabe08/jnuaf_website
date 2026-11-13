<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\View\View;



class SiteVersionController extends Controller

{

    public function publish(Request $request, $id)
    {
        $v = \DB::table('site_versions')->where('id', $id)->first();
        if (! $v) { abort(404); }
        \DB::table('site_versions')->where('page_key', $v->page_key)->where('status', 'published')->update(['status' => 'archived']);
        \DB::table('site_versions')->where('id', $id)->update(['status' => 'published', 'published_at' => now()]);
        return redirect()->route('admin.versions.index')->with('status', "Version publi\u{e9}e.");
    }

    public function index(Request $request): View

    {

        $versions = \DB::table('site_versions')

            ->orderBy('page_key')

            ->orderByDesc('version')

            ->get()

            ->groupBy('page_key');



        return view('admin.versions.index', compact('versions'));

    }

}

