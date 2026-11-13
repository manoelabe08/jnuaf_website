<?php



namespace App\Http\Controllers\Public;



use App\Http\Controllers\Controller;



class PageController extends Controller

{

    public function about() { return view('pages.about'); }

    public function programs() { return view('pages.programs'); }

    public function governance() { return view('pages.governance'); }

    public function faq() { return view('pages.faq'); }

}

