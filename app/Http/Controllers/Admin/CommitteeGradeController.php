<?php



namespace App\Http\Controllers\Admin;



use App\Enums\ApplicationStatus;

use App\Http\Controllers\Controller;

use App\Models\CommitteeApplication;

use App\Models\Territory;

use App\Services\SensitiveActionService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use Illuminate\View\View;



class CommitteeGradeController extends Controller

{

    public static function grades(): array

    {

        return [

            'member' => "Membre",

            'pole_lead' => "Responsable de p\u{f4}le",

            'city_colead' => 
"Co-r\u{e9}f\u{e9}rent de ville",



            'country_lead' => "Pr\u{e9}sident(e) du p\u{f4}le pays",


        ];

    }



    public function index(Request $request): View

    {

        $applications = CommitteeApplication::query()

            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))

            ->orderByRaw("(status = 'accepted') desc")

            ->latest()

            ->paginate(30)

            ->withQueryString();



        return view('admin.committee.grades', [

            'applications' => $applications,

            'statuses' => ApplicationStatus::options(),

            'grades' => self::grades(),

            'cities' => Territory::orderBy('city')->pluck('city', 'id'),

        ]);

    }



    public function update(Request $request, CommitteeApplication $application): RedirectResponse

    {

        abort_unless($request->user()->isAdmin(), 403);



        $data = $request->validate([

            'grade' => ['required', Rule::in(array_keys(self::grades()))],

            'territory_id' => ['nullable', 'integer', 'exists:territories,id'],

        ]);



        if ($application->status->value !== 'accepted') {

            return back()->withErrors(['grade' => "Seules les candidatures accept\u{e9}es peuvent recevoir un grade."]);

        }



        $oldGrade = $application->grade;

        $oldTerritory = $application->territory_id;

        $gradeChanged = $oldGrade !== $data['grade'];



        $application->forceFill([

            'grade' => $data['grade'],

            'territory_id' => $data['territory_id'] ?? null,

        ] + ($gradeChanged ? [

            'grade_changed_by' => $request->user()->id,

            'grade_changed_at' => now(),

        ] : []))->save();



        try {

            app(SensitiveActionService::class)->log($request, $request->user(), 'committee_grade', [

                'application' => $application->id,

                'from' => $oldGrade,

                'to' => $data['grade'],

                'territory_from' => $oldTerritory,

                'territory_to' => $data['territory_id'] ?? null,

            ]);

        } catch (\Throwable $e) {

            report($e);

        }



        return back()->with('status', "Grade enregistr\u{e9} pour ".$application->full_name.'.');

    }

}

