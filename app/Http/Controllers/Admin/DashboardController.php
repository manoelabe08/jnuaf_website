<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\Profile;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\CommitteeApplication;
use App\Models\PartnerInquiry;
use App\Models\Registration;
use App\Models\Territory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $base = fn () => Registration::currentEdition()->visibleTo($user)
            ->where('status', RegistrationStatus::Confirmed);

        $byTerritory = $base()->toBase()->selectRaw('territory_id, count(*) as total, sum(case when checked_in_at is not null then 1 else 0 end) as present')
            ->groupBy('territory_id')->get()->keyBy('territory_id');

        $byProfile = $base()->toBase()->selectRaw('profile, count(*) as total')->groupBy('profile')->pluck('total', 'profile');

        $territories = Territory::active()->get()
            ->when(! $user->isAdmin(), fn ($c) => $c->where('id', $user->territory_id));

        return view('admin.dashboard', [
            'total' => $base()->count(),
            'present' => $base()->whereNotNull('checked_in_at')->count(),
            'cancelled' => Registration::currentEdition()->visibleTo($user)->where('status', RegistrationStatus::Cancelled)->count(),
            'imageConsent' => $base()->where('consent_image', true)->count(),
            'territories' => $territories,
            'byTerritory' => $byTerritory,
            'byProfile' => collect(Profile::cases())->mapWithKeys(fn ($p) => [$p->label() => (int) ($byProfile[$p->value] ?? 0)]),
            'latest' => $base()->with('territory')->latest()->limit(8)->get(),
            'newPartners' => $user->isAdmin() ? PartnerInquiry::where('status', 'new')->count() : 0,
            'newApplications' => $user->isAdmin() ? CommitteeApplication::where('status', ApplicationStatus::New)->count() : null,
        ]);
    }
}
