<?php



namespace App\Services;



use App\Models\NewsletterDelivery;

use Carbon\Carbon;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\RateLimiter;



class NewsletterEta

{

    private const FORCED_PER_MINUTE = 8;



    public static function queue(): array

    {

        $ids = NewsletterDelivery::where('status', 'pending')

            ->whereHas('campaign', fn ($q) => $q->whereIn('status', ['queued', 'sending']))

            ->orderByRaw('prioritized_at is null')

            ->orderByDesc('prioritized_at')

            ->orderBy('id')

            ->pluck('id')

            ->all();



        return array_flip($ids);

    }



    public static function timeline(int $max): array

    {

        $win = [60, 3600, 86400];

        $cap = [

            max(1, (int) config('newsletter.per_minute')),

            max(1, (int) config('newsletter.per_hour')),

            max(1, (int) config('newsletter.per_day')),

        ];

        $keys = ['jnuaf-newsletter-minute', 'jnuaf-newsletter-hour', 'jnuaf-newsletter-day'];

        $used = [];

        $reset = [];

        foreach ($keys as $i => $key) {

            $used[$i] = RateLimiter::attempts($key);

            $reset[$i] = $used[$i] > 0 ? RateLimiter::availableIn($key) : 0;

        }



        $times = [];

        $t = 0;

        $cool = Cache::get('jnuaf-newsletter-cooldown');

        if ($cool) {

            try {

                $t = max(0, 3600 - Carbon::parse($cool)->diffInSeconds(now(), true));

            } catch (\Throwable $e) {

                $t = 3600;

            }

        }



        $forced = $cool ? 0 : min($max, max(0, (int) Cache::get('jnuaf-newsletter-force', 0)));

        for ($n = 1; $n <= $forced; $n++) {

            $times[$n] = (int) round($n * 60 / self::FORCED_PER_MINUTE);

        }

        if ($forced > 0) {

            $t = $times[$forced];

            foreach ($keys as $i => $key) {

                if ($used[$i] > 0 && $t >= $reset[$i]) {

                    $used[$i] = 0;

                }

                if ($used[$i] === 0) {

                    $reset[$i] = $t + $win[$i];

                }

                $used[$i] += $forced;

            }

        }



        $n = $forced;

        while ($n < $max && $t < 14 * 86400) {

            foreach ($keys as $i => $key) {

                if ($used[$i] > 0 && $t >= $reset[$i]) {

                    $used[$i] = 0;

                }

            }

            $can = min($cap[0] - $used[0], $cap[1] - $used[1], $cap[2] - $used[2]);

            $can = max(0, min($can, $max - $n));

            for ($k = 0; $k < $can; $k++) {

                $n++;

                foreach ($keys as $i => $key) {

                    if ($used[$i] === 0) {

                        $reset[$i] = $t + $win[$i];

                    }

                    $used[$i]++;

                }

                $times[$n] = (int) $t;

            }

            if ($n >= $max) {

                break;

            }

            $t += 60;

        }



        return $times;

    }



    public static function forPage($deliveries): array

    {

        $queue = self::queue();

        $positions = [];

        $max = 0;

        foreach ($deliveries as $d) {

            if ($d->status === 'pending' && isset($queue[$d->id])) {

                $positions[$d->id] = $queue[$d->id] + 1;

                $max = max($max, $queue[$d->id] + 1);

            }

        }

        if ($max === 0) {

            return [];

        }

        $times = self::timeline($max);

        $result = [];

        foreach ($positions as $id => $pos) {

            $result[$id] = ['position' => $pos, 'seconds' => $times[$pos] ?? null];

        }



        return $result;

    }




    public static function listing($campaign, $request)

    {

        $query = $campaign->deliveries();

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {

            $query->where('email', 'like', '%'.addcslashes($search, '%_\\').'%');

        }

        $state = (string) $request->query('etat', '');

        if (in_array($state, ['pending', 'processing', 'sent', 'failed', 'uncertain', 'skipped'], true)) {

            $query->where('status', $state);

        }



        return $query

            ->orderByRaw("status = 'pending'")

            ->orderByRaw('coalesce(sent_at, processing_at) is null')

            ->orderByRaw('coalesce(sent_at, processing_at)')

            ->orderByRaw('prioritized_at is null')

            ->orderByDesc('prioritized_at')

            ->orderBy('id')

            ->paginate(40)

            ->withQueryString();

    }




    public static function format(int $seconds): string

    {

        $days = intdiv($seconds, 86400);

        $rest = $seconds % 86400;

        $hms = sprintf('%02d:%02d:%02d', intdiv($rest, 3600), intdiv($rest % 3600, 60), $rest % 60);



        return $days > 0 ? $days.' j '.$hms : $hms;

    }

}

