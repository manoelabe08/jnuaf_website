<?php



namespace App\Http\Controllers;



use Illuminate\Support\Facades\Storage;



class MediaController extends Controller

{

    public function show(string $path)

    {

        if (strpos($path, '..') !== false || !preg_match('#^posts/[A-Za-z0-9/_.-]+\.(jpe?g|png|gif|webp)$#i', $path)) {

            abort(404);

        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {

            abort(404);

        }

        return response()->file($disk->path($path), ['Cache-Control' => 'public, max-age=86400']);

    }

}

