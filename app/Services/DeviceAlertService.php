<?php



namespace App\Services;



use App\Mail\NewDeviceLogin;

use App\Models\KnownDevice;

use App\Models\SecurityEvent;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Mail;



class DeviceAlertService

{

    private const MAX_DEVICES = 20;



    public function check(?User $user, Request $request): void

    {

        if (! $user) {

            return;

        }



        try {

            $this->handle($user, $request);

        } catch (\Throwable $exception) {

            report($exception);

        }

    }



    private function handle(User $user, Request $request): void

    {

        [$browser, $system] = $this->describe((string) $request->userAgent());

        $label = $browser.' sur '.$system;

        $signature = hash('sha256', mb_strtolower($browser.'|'.$system));

        $masked = $this->maskIp((string) $request->ip());



        $known = KnownDevice::where('user_id', $user->id)->where('signature', $signature)->first();

        if ($known) {

            $known->update(['last_seen_at' => now(), 'last_ip' => $masked]);



            return;

        }



        $first = ! KnownDevice::where('user_id', $user->id)->exists();



        KnownDevice::create([

            'user_id' => $user->id,

            'signature' => $signature,

            'label' => $label,

            'last_ip' => $masked,

            'first_seen_at' => now(),

            'last_seen_at' => now(),

        ]);



        $stale = KnownDevice::where('user_id', $user->id)->orderByDesc('last_seen_at')->orderByDesc('id')->skip(self::MAX_DEVICES)->take(1000)->pluck('id');

        if ($stale->isNotEmpty()) {

            KnownDevice::whereIn('id', $stale)->delete();

        }



        if ($first) {

            return;

        }



        SecurityEvent::create([

            'user_id' => $user->id,

            'action' => 'connexion : nouvel appareil',

            'details' => ['Appareil' => $label],

            'ip' => $request->ip(),

            'user_agent' => substr((string) $request->userAgent(), 0, 500),

        ]);



        Mail::to($user->email)->send(new NewDeviceLogin((string) $user->name, $label, $masked, now()));

    }



    /** @return array{0: string, 1: string} */

    private function describe(string $ua): array

    {

        $browser = match (true) {

            str_contains($ua, 'Edg/') || str_contains($ua, 'EdgA/') || str_contains($ua, 'EdgiOS/') => 'Edge',

            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',

            str_contains($ua, 'Firefox/') || str_contains($ua, 'FxiOS/') => 'Firefox',

            str_contains($ua, 'Chrome/') || str_contains($ua, 'CriOS/') => 'Chrome',

            str_contains($ua, 'Safari/') => 'Safari',

            default => 'Navigateur inconnu',

        };



        $system = match (true) {

            str_contains($ua, 'Android') => 'Android',

            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',

            str_contains($ua, 'Windows') => 'Windows',

            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS X') => 'macOS',

            str_contains($ua, 'CrOS') => 'ChromeOS',

            str_contains($ua, 'Linux') => 'Linux',

            default => 'système inconnu',

        };



        return [$browser, $system];

    }



    private function maskIp(string $ip): string

    {

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {

            $parts = explode('.', $ip);



            return $parts[0].'.'.$parts[1].'.x.x';

        }



        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {

            $parts = explode(':', $ip);



            return ($parts[0] ?? '').':'.($parts[1] ?? '').':…';

        }



        return 'inconnue';

    }

}

