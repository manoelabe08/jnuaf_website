<?php



namespace App\Http\Controllers;



use App\Models\MemberCard;

use Illuminate\Http\Response;

use Illuminate\View\View;



class CardPublicController extends Controller

{

    public function verify(string $token): Response

    {

        $card = MemberCard::query()->with('territory')->where('verify_token', $token)->first();



        return response()

            ->view('card.verify', ['card' => $card], $card === null ? 404 : 200)

            ->withHeaders([

                'Cache-Control' => 'no-store, private',

                'X-Robots-Tag' => 'noindex, nofollow',

                'Referrer-Policy' => 'no-referrer',

            ]);

    }



    public function info(): View

    {

        return view('card.info', [

            'retentionMonths' => (int) config('jnuaf_cards.retention_months'),

            'contact' => (string) config('jnuaf_cards.contact_email'),

        ]);

    }

}

