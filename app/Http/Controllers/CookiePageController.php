<?php



namespace App\Http\Controllers;



use Illuminate\Contracts\View\View;

use Illuminate\Support\Facades\Auth;



class CookiePageController extends Controller

{

    public function __invoke(): View

    {

        return view('cookies', [

            'sessionCookie' => (string) config('session.cookie'),

            'sessionMinutes' => (int) config('session.lifetime'),

            'rememberDays' => $this->rememberDays(),

        ]);

    }



    private function rememberDays(): ?int

    {

        try {

            $guard = Auth::guard('web');

            $property = new \ReflectionProperty($guard, 'rememberDuration');

            $property->setAccessible(true);

            $minutes = (int) $property->getValue($guard);



            return $minutes > 0 ? (int) round($minutes / 1440) : null;

        } catch (\Throwable) {

            return null;

        }

    }

}

