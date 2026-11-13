<?php



namespace App\Http\Controllers\Admin;



use App\Enums\ApplicationStatus;

use App\Enums\Profile;

use App\Enums\RegistrationStatus;

use App\Http\Controllers\Controller;

use App\Models\CommitteeApplication;

use App\Models\MemberCard;

use App\Models\PartnerInquiry;

use App\Models\Registration;

use App\Models\SensitiveAction;

use App\Models\Territory;

use App\Models\User;

use App\Services\SensitiveActionService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\DB;

use Illuminate\View\View;

use Symfony\Component\HttpKernel\Exception\HttpException;



class DashboardV2Controller extends Controller

{

    public function __invoke(Request $request): Response|RedirectResponse

    {

        try {

            return response($this->build($request)->render());

        } catch (HttpException $e) {

            throw $e;

        } catch (\Throwable $e) {

            report($e);



            return redirect()->route('admin.dashboard.old')

                ->with('status', "Le nouveau tableau de bord a rencontr\u{e9} une erreur : ancienne version affich\u{e9}e.");

        }

    }



    private function build(Request $request): View

    {

        $user = $request->user();

        $isAdmin = $user->isAdmin();

        $isSuper = $isAdmin && $user->isSuperAdmin();

        abort_unless($isAdmin || $user->territory_id, 404);



        $days = (int) $request->input('jours', 30);

        $days = in_array($days, [7, 30, 90], true) ? $days : 30;

        $cityId = $isAdmin ? (int) $request->input('ville', 0) : (int) $user->territory_id;



        $base = fn () => Registration::currentEdition()->visibleTo($user)

            ->where('status', RegistrationStatus::Confirmed)

            ->when($cityId > 0, fn ($q) => $q->where('territory_id', $cityId));



        $total = $base()->count();

        $present = $base()->whereNotNull('checked_in_at')->count();

        $imageConsent = $base()->where('consent_image', true)->count();

        $cancelled = Registration::currentEdition()->visibleTo($user)

            ->when($cityId > 0, fn ($q) => $q->where('territory_id', $cityId))

            ->where('status', RegistrationStatus::Cancelled)->count();

        $rate = $total ? (int) round($present / $total * 100) : 0;



        $from = now()->subDays($days - 1)->startOfDay();

        $prevFrom = $from->copy()->subDays($days);

        $cur = $base()->where('created_at', '>=', $from)->count();

        $prev = $base()->where('created_at', '>=', $prevFrom)->where('created_at', '<', $from)->count();

        $delta = $prev > 0 ? (int) round(($cur - $prev) / $prev * 100) : null;



        $daily = $base()->toBase()->where('created_at', '>=', $from)

            ->selectRaw('date(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');

        $cum = $base()->where('created_at', '<', $from)->count();

        $series = [];

        for ($i = 0; $i < $days; $i++) {

            $key = $from->copy()->addDays($i)->toDateString();

            $cum += (int) ($daily[$key] ?? 0);

            $series[] = $cum;

        }

        $max = max(1, max($series));

        $w = 600;

        $h = 150;

        $step = $days > 1 ? $w / ($days - 1) : $w;

        $pts = [];

        foreach ($series as $i => $v) {

            $pts[] = round($i * $step, 1).','.round(8 + ($h - 16) * (1 - $v / $max), 1);

        }

        $line = implode(' ', $pts);

        $area = '0,'.$h.' '.$line.' '.$w.','.$h;



        $byTerritory = $base()->toBase()

            ->selectRaw('territory_id, count(*) as total, sum(case when checked_in_at is not null then 1 else 0 end) as present')

            ->groupBy('territory_id')->get()->keyBy('territory_id');

        $territories = Territory::active()->get();

        if (! $isAdmin) {

            $territories = $territories->where('id', $user->territory_id)->values();

        }

        $cities = $territories->map(function ($t) use ($byTerritory) {

            $n = (int) ($byTerritory[$t->id]->total ?? 0);

            $cap = $t->capacity ? (int) $t->capacity : null;



            return [

                'id' => $t->id,

                'city' => $t->city,

                'total' => $n,

                'present' => (int) ($byTerritory[$t->id]->present ?? 0),

                'capacity' => $cap,

                'pct' => $cap ? (int) round($n / $cap * 100) : null,

            ];

        })->sortByDesc('total')->values()->all();

        $maxCity = max(1, max(array_column($cities, 'total') ?: [0]));



        $byProfile = $base()->toBase()->selectRaw('profile, count(*) as total')->groupBy('profile')->pluck('total', 'profile');

        $palette = ['#14592A', '#E8761B', '#2E8B57', '#F2A65A', '#6B8E23', '#B8860B', '#4F7942', '#D2691E'];

        $segments = [];

        $acc = 0;

        $k = 0;

        foreach (Profile::cases() as $p) {

            $n = (int) ($byProfile[$p->value] ?? 0);

            if (! $n) {

                continue;

            }

            $pct = $total ? $n / $total * 100 : 0;

            $segments[] = [

                'label' => $p->label(),

                'count' => $n,

                'dash' => round($pct, 2).' '.round(100 - $pct, 2),

                'offset' => round(25 - $acc, 2),

                'color' => $palette[$k++ % count($palette)],

            ];

            $acc += $pct;

        }$latest = $base()->with('territory')->latest()->limit(8)->get();



        $funnel = [];

        $maxFunnel = 1;

        $cards = null;

        $todo = [];

        $latestApps = collect();

        $byCountry = [];

        $byPole = [];

        $recent = [];
        $campaign = null;

        if ($isAdmin) {

            $cs = CommitteeApplication::query()->toBase()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

            foreach (ApplicationStatus::cases() as $s) {

                $funnel[] = ['label' => $s->label(), 'n' => (int) ($cs[$s->value] ?? 0)];

            }

            $maxFunnel = max(1, max(array_column($funnel, 'n') ?: [0]));

            $latestApps = CommitteeApplication::latest()->limit(5)->get();

            $byCountry = CommitteeApplication::query()->toBase()->selectRaw('country, count(*) as c')->groupBy('country')->orderByDesc('c')->limit(6)->pluck('c', 'country')->all();

            $byPole = CommitteeApplication::query()->toBase()->selectRaw('coalesce(assigned_pole, pole) as pole_eff, count(*) as c')->groupByRaw('coalesce(assigned_pole, pole)')->orderByDesc('c')->limit(6)->pluck('c', 'pole_eff')->all();



            $cq = fn () => MemberCard::query()->when($cityId > 0, fn ($q) => $q->where('territory_id', $cityId));

            $cards = [

                'valid' => $cq()->whereNull('revoked_at')->count(),

                'sent' => $cq()->whereNull('revoked_at')->whereNotNull('sent_at')->count(),

                'unsent' => $cq()->whereNull('revoked_at')->whereNull('sent_at')->count(),

                'revoked' => $cq()->whereNotNull('revoked_at')->count(),

            ];



            $camp = DB::table('newsletter_campaigns')->orderByDesc('id')->first();

            if ($camp) {

                $st = DB::table('newsletter_deliveries')->where('newsletter_campaign_id', $camp->id)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

                $nSent = (int) ($st['sent'] ?? 0);

                $nPend = (int) ($st['pending'] ?? 0);

                $nFail = (int) ($st['failed'] ?? 0);

                $nAll = max(1, $nSent + $nPend + $nFail);

                $campaign = [

                    'title' => (string) $camp->title,

                    'status' => (string) $camp->status,

                    'sent' => $nSent,

                    'pending' => $nPend,

                    'failed' => $nFail,

                    'pct' => (int) round($nSent / $nAll * 100),

                    'fail_pct' => round($nFail / $nAll * 100, 1),

                    'days' => $nPend ? (int) ceil($nPend / 900) : 0,

                ];

                if ($campaign['fail_pct'] >= 5) {

                    $todo[] = ['n' => $nFail, 'text' => "envoi(s) newsletter en \u{e9}chec (".$campaign['fail_pct'].' %)', 'url' => route('admin.newsletter.index')];

                }

            }
            $newApps = CommitteeApplication::where('status', ApplicationStatus::New)->count();

            if ($newApps) {

                $todo[] = ['n' => $newApps, 'text' => "candidature(s) comit\u{e9} \u{e0} traiter", 'url' => route('admin.committee.index', ['status' => 'new'])];

            }

            $noPhoto = CommitteeApplication::where('status', ApplicationStatus::Accepted)->whereNull('photo_path')->count();

            if ($noPhoto) {

                $todo[] = ['n' => $noPhoto, 'text' => "candidature(s) accept\u{e9}e(s) sans photo", 'url' => route('admin.committee.index', ['status' => 'accepted'])];

            }

            $conflicts = DB::table('committee_pole_proposals')->whereIn('status', ['declined', 'expired'])->count();
            if ($conflicts) {
                $todo[] = ['n' => $conflicts, 'text' => "conflit(s) de p\u{f4}le \u{e0} r\u{e9}soudre", 'url' => route('admin.committee.index')];
            }
            if ($cards['unsent']) {

                $todo[] = ['n' => $cards['unsent'], 'text' => "carte(s) de membre \u{e0} envoyer", 'url' => route('admin.cards.index')];

            }

            $newPartners = PartnerInquiry::where('status', 'new')->count();

            if ($newPartners) {

                $todo[] = ['n' => $newPartners, 'text' => 'demande(s) de partenariat nouvelle(s)', 'url' => route('admin.partners.index', ['status' => 'new'])];

            }

            if ($isSuper) {

                $pending = SensitiveAction::where('status', 'pending')->where('expires_at', '>', now())->count();

                if ($pending) {

                    $todo[] = ['n' => $pending, 'text' => 'validation(s) en attente', 'url' => route('admin.sensitive.index')];

                }

                $deleting = User::whereNotNull('anonymize_after')->whereNull('anonymized_at')->count();

                if ($deleting) {

                    $todo[] = ['n' => $deleting, 'text' => 'compte(s) en cours de suppression (30 jours)', 'url' => route('admin.users.index')];

                }

                $trash = DB::table('trash_items')->whereNull('restored_at')->count();

                if ($trash) {

                    $todo[] = ['n' => $trash, 'text' => "\u{e9}l\u{e9}ment(s) dans la corbeille", 'url' => route('admin.trash.index')];

                }

                $svc = app(SensitiveActionService::class);

                $recent = SensitiveAction::with('user')->latest()->limit(6)->get()->map(fn ($a) => [

                    'label' => (string) $svc->label($a->action_key),

                    'who' => (string) $a->user?->name,

                    'status' => (string) $a->status,

                    'at' => $a->created_at->format('d/m H:i'),

                ])->all();

            }

        }



        $today = now()->startOfDay();

        $eventDate = Carbon::parse(config('jnuaf.event_date'))->startOfDay();

        $daysLeft = (int) $today->diffInDays($eventDate, false);

        $milestones = collect([

            ['date' => '2026-12-14', 'label' => 'Campagne newsletter'],

            ['date' => '2027-03-04', 'label' => 'Dates Paris et Dijon'],

            ['date' => $eventDate->toDateString(), 'label' => "Journ\u{e9}e du Num\u{e9}rique au F\u{e9}minin"],

        ])->map(function ($m) use ($today) {

            $d = Carbon::parse($m['date'])->startOfDay();



            return $m + ['left' => (int) $today->diffInDays($d, false), 'fmt' => $d->format('d/m/Y')];

        })->filter(fn ($m) => $m['left'] >= 0)->take(4)->values()->all();



        $health = null;

        if ($isSuper) {

            $ts = 0;

            try {

                foreach (glob('/home/jnuaf-sauvegardes/*') ?: [] as $f) {

                    $ts = max($ts, (int) @filemtime($f));

                }

            } catch (\Throwable $e) {

                $ts = 0;

            }

            $beat = Cache::get('cron_heartbeat');

            $errors = null;

            $log = storage_path('logs/laravel.log');

            if (is_file($log)) {

                $size = (int) filesize($log);

                $fh = fopen($log, 'rb');

                if ($fh) {

                    fseek($fh, max(0, $size - 300000));

                    $errors = substr_count((string) stream_get_contents($fh), '.ERROR:');

                    fclose($fh);

                }

            }

            $health = [

                'backup_age_h' => $ts ? (int) floor((time() - $ts) / 3600) : null,

                'cron_known' => (bool) $beat,

                'cron_ok' => $beat && (time() - (int) $beat) < 300,

                'errors' => $errors,

            ];

        }return view('admin.dashboard2', compact(

            'user', 'isAdmin', 'isSuper', 'days', 'cityId', 'total', 'present', 'imageConsent', 'cancelled', 'rate',

            'cur', 'delta', 'line', 'area', 'max', 'cities', 'maxCity', 'segments', 'funnel', 'maxFunnel',

            'todo', 'territories', 'cards', 'health', 'daysLeft', 'milestones', 'latest', 'latestApps',

            'byCountry', 'byPole', 'recent', 'campaign'

        ) + ['fromLabel' => $from->format('d/m'), 'toLabel' => now()->format('d/m')]);

    }

}

