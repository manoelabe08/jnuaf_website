<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\Registration;

use App\Models\Territory;

use App\Services\BadgeService;
use App\Services\MemberCardService;

use Illuminate\Http\Request;

use Illuminate\Http\Response;



class DocumentPreviewController extends Controller

{

    public function attestation(Request $request, BadgeService $badges): Response

    {

        abort_unless($request->user()?->isAdmin(), 403);



        return $this->pdf($badges->attestationPdf($this->sample()), 'apercu-attestation.pdf');

    }




    public function card(Request $request, MemberCardService $cards): Response

    {

        abort_unless($request->user()?->isAdmin(), 403);



        $category = (string) $request->query('categorie', 'CO');



        if (! array_key_exists($category, MemberCardService::CATEGORIES)) {

            $category = 'CO';

        }



        return $this->pdf($cards->pdf($cards->sample($category)), 'apercu-carte.pdf');

    }


    private function pdf(string $content, string $name): Response

    {

        return response($content, 200, [

            'Content-Type' => 'application/pdf',

            'Content-Disposition' => 'inline; filename="'.$name.'"',

        ]);

    }



    private function sample(): Registration

    {

        $territory = Territory::query()->orderBy('position')->first();



        abort_if($territory === null, 404, 'Aucun territoire configuré.');



        $registration = new Registration();

        $registration->forceFill([

            'edition' => (string) config('jnuaf.edition'),

            'profile' => 'participante',

            'first_name' => 'Aya',

            'last_name' => 'Kouassi',

            'badge_code' => 'JNUAF27-APERCU',

        ]);

        $registration->setRelation('territory', $territory);



        return $registration;

    }

}

