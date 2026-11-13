<?php



namespace App\Http\Controllers;



use App\Services\PhotoCollectionService;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

use Illuminate\View\View;



class PhotoCollectionController extends Controller

{

    public function show(string $token, PhotoCollectionService $svc): View

    {

        $req = $svc->find($token);



        return view('photo.upload', ['state' => $req ? 'form' : 'invalid', 'token' => $token, 'first' => $req ? $this->first($req) : '', 'reason' => $req ? (string) ($req->reason ?? '') : '', 'askCv' => $req ? $svc->needsCv((string) $req->email) : false]);

    }



    public function store(Request $request, string $token, PhotoCollectionService $svc): View

    {

        $req = $svc->find($token);

        if (! $req) {

            return view('photo.upload', ['state' => 'invalid', 'token' => $token, 'first' => '']);

        }

        $request->validate([

            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192'],

            'cv' => ['nullable', 'file', 'mimes:pdf', 'max:4096'],
            'consent' => ['accepted'],

        ], [

            
'photo.required' => 'Choisissez une photo.',

            'cv.mimes' => "Le CV doit \u{ea}tre au format PDF.",

            'cv.max' => 'Le CV doit faire 4 Mo au plus.',


            'photo.mimes' => "Formats accept\u{e9}s : JPG, PNG ou WebP.",

            'photo.max' => 'La photo doit faire 8 Mo au plus.',

            'photo.uploaded' => "L'envoi a \u{e9}chou\u{e9} : photo trop lourde pour le serveur. R\u{e9}duisez-la puis r\u{e9}essayez.",

            'consent.accepted' => "Cochez la case pour autoriser l'utilisation de votre photo.",

        ]);

        $tmp = null;

        try {

            $tmp = $svc->normalize($request->file('photo'));

            $done = $svc->apply((string) $req->email, $tmp, (bool) ($req->replace ?? false));
            $done = array_merge($done, $svc->applySpeakers((string) $req->email, $tmp, $request->file('cv'), (bool) ($req->replace ?? false)));

        } finally {

            if ($tmp && is_file($tmp)) {

                @unlink($tmp);

            }

        }

        $svc->markUsed((int) $req->id);

        Log::info('Collecte de photos : photo recue', ['request_id' => $req->id, 'mises_a_jour' => $done]);



        return view('photo.upload', ['state' => 'done', 'token' => $token, 'first' => $this->first($req)]);

    }



    private function first(object $req): string

    {

        return app(\App\Services\PhotoCollectionService::class)->givenName((string) $req->name);

    }

}

