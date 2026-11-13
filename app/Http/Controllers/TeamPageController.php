<?php



namespace App\Http\Controllers;



use App\Enums\ApplicationStatus;

use App\Models\CommitteeApplication;

use App\Models\Territory;

use App\Services\PhotoCollectionService;

use Illuminate\Database\Eloquent\Builder;

use Illuminate\Http\Response;

use Illuminate\View\View;

use Symfony\Component\HttpFoundation\BinaryFileResponse;



class TeamPageController extends Controller

{

    /** Personnes dont la photo, le nom et le pole peuvent etre affiches publiquement. */

    public static function eligible(): Builder

    {

        return CommitteeApplication::query()

            ->where('status', ApplicationStatus::Accepted->value)
            ; // TEST TEMPORAIRE : conditions photo et consentement desactivees

    }



    public function index(): View

    {

        $territories = Territory::all()->keyBy('id');

        $rank = ['country_lead' => 0, 'city_colead' => 1, 'pole_lead' => 2];



        $members = self::eligible()->get()->all();

        usort($members, function ($a, $b) use ($rank) {

            return [$rank[(string) $a->grade] ?? 3, mb_strtolower((string) $a->full_name)]

                <=> [$rank[(string) $b->grade] ?? 3, mb_strtolower((string) $b->full_name)];

        });



        $groups = [];

        foreach ($members as $m) {

            $territory = $m->territory_id ? $territories->get($m->territory_id) : null;

            $country = $territory?->country ?: (string) $m->country;

            $city = $territory?->city ?: '';

            $groups[$country][$city][] = [

                'id' => $m->id,

                'name' => (string) $m->full_name,

                'pole' => (string) ($m->assigned_pole ?: $m->pole),

                'v' => substr(md5((string) ($m->photo_path ?: 'sans-photo')), 0, 8),
                'photo' => (string) $m->photo_path,

            ];

        }

        ksort($groups);

        foreach ($groups as &$cities) {

            ksort($cities);

        }

        unset($cities);



        return view('v2.team', ['groups' => $groups]);

    }



    public function photo(int $application, string $v): BinaryFileResponse|Response

    {

        $member = self::eligible()->whereKey($application)->first();

        abort_unless($member, 404);



        $path = app(PhotoCollectionService::class)->committeeRoot().'/'.basename((string) $member->photo_path);

        abort_unless(is_file($path), 404);



        return response()->file($path, [

            'Content-Type' => 'image/jpeg',

            'Cache-Control' => 'public, max-age=300',

            'X-Content-Type-Options' => 'nosniff',

        ]);

    }

}

