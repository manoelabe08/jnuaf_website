<?php



namespace App\Http\Controllers;



use App\Models\SponsorLogo;

use Illuminate\Support\Facades\Storage;

use Symfony\Component\HttpFoundation\BinaryFileResponse;



class SponsorLogoController extends Controller

{

    public function show(SponsorLogo $logo): BinaryFileResponse

    {

        abort_unless($logo->is_active || auth()->check(), 404);



        $path = Storage::disk('local')->path('sponsors/'.$logo->path);

        abort_unless(is_file($path), 404);



        return response()->file($path, [

            'Content-Type' => $logo->mime,

            'Cache-Control' => 'public, max-age=86400',

            'X-Content-Type-Options' => 'nosniff',

        ]);

    }

}

