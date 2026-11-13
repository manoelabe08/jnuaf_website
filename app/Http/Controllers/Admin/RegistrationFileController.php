<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Registration;

use App\Services\PhotoCollectionService;

use Symfony\Component\HttpFoundation\BinaryFileResponse;



class RegistrationFileController extends Controller

{

    public function show(Registration $registration, string $type): BinaryFileResponse

    {

        abort_unless(in_array($type, ['photo', 'cv'], true), 404);

        $name = (string) ($type === 'cv' ? $registration->cv_path : $registration->photo_path);

        $ext = $type === 'cv' ? 'pdf' : 'jpg';

        abort_unless(preg_match('/^\d+-[a-z0-9]{24}\.'.$ext.'$/', $name) === 1, 404);

        $file = app(PhotoCollectionService::class)->registrationRoot().'/'.$name;

        abort_unless(is_file($file), 404);



        return response()->file($file, [

            'Content-Type' => $type === 'cv' ? 'application/pdf' : 'image/jpeg',

            'Cache-Control' => 'private, max-age=600',

            'X-Content-Type-Options' => 'nosniff',

        ]);

    }

}

