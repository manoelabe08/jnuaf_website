<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Mail\PhotoRejectedMail;

use App\Models\CommitteeApplication;

use App\Services\PhotoCollectionService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Mail;

use Illuminate\Validation\Rule;

use Symfony\Component\HttpFoundation\BinaryFileResponse;class CommitteePhotoController extends Controller

{

    private const REASONS = [

        'blurry' => "Photo floue, trop sombre ou de mauvaise qualit\u{e9}",

        'face' => "Visage non visible ou partiellement cach\u{e9} (lunettes de soleil, masque, casquette)",

        'group' => "Photo de groupe ou ne vous montrant pas seul(e)",

        'presentable' => "Photo non pr\u{e9}sentable (filtre, selfie de c\u{f4}t\u{e9}, arri\u{e8}re-plan charg\u{e9})",

        'other' => 'Autre motif',

    ];



    public function show(CommitteeApplication $application): BinaryFileResponse

    {

        $name = (string) $application->photo_path;

        abort_unless(preg_match('/^\d+-[a-z0-9]{24}\.jpg$/', $name) === 1, 404);

        $file = app(PhotoCollectionService::class)->committeeRoot().'/'.$name;

        abort_unless(is_file($file), 404);



        return response()->file($file, [

            'Content-Type' => 'image/jpeg',

            'Cache-Control' => 'private, max-age=600',

            'X-Content-Type-Options' => 'nosniff',

        ]);

    }



    public function reject(Request $request, CommitteeApplication $application, PhotoCollectionService $svc): RedirectResponse

    {

        $data = $request->validate([

            'reason_code' => ['required', Rule::in(array_keys(self::REASONS))],

            'reason_note' => ['nullable', 'string', 'max:200'],

        ]);

        $reason = self::REASONS[$data['reason_code']];

        if (! empty($data['reason_note'])) {

            $reason .= ' - '.trim($data['reason_note']);

        }

        $name = (string) $application->photo_path;

        if (preg_match('/^\d+-[a-z0-9]{24}\.jpg$/', $name) === 1) {

            @unlink($svc->committeeRoot().'/'.$name);

        }

        $application->forceFill([

            'photo_path' => null,

            'photo_consented_at' => null,

            'photo_rejected_at' => now(),

            'photo_reject_reason' => mb_substr($reason, 0, 300),

        ])->save();



        $email = mb_strtolower((string) $application->email);

        $raw = $svc->createRequest($email, $application->full_name, true, $reason);

        try {

            Mail::mailer('team')->to($email)->send(new PhotoRejectedMail(

                $svc->givenName($application->full_name),

                url('/photo/'.$raw),

                PhotoCollectionService::DAYS,

                $reason

            ));

            DB::table('photo_requests')->where('token_hash', hash('sha256', $raw))->update(['sent_at' => now()]);

            $status = "Photo refus\u{e9}e : un mail avec un nouveau lien a \u{e9}t\u{e9} envoy\u{e9} \u{e0} la personne.";

        } catch (\Throwable $exception) {

            report($exception);

            DB::table('photo_requests')->where('token_hash', hash('sha256', $raw))->delete();

            $status = "Photo retir\u{e9}e, mais le mail n'a pas pu \u{ea}tre envoy\u{e9}. R\u{e9}essayez ou contactez la personne.";

        }

        Log::info('Photo de candidature refusee', ['candidature_id' => $application->id, 'acteur_id' => $request->user()->id, 'motif' => $reason]);



        return back()->with('status', $status);

    }

}

