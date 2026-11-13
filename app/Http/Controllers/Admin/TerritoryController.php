<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TerritoryController extends Controller
{
    public function index(): View
    {
        $edition = config('jnuaf.edition');

        return view('admin.territories.index', [
            'territories' => Territory::orderBy('position')
                ->withCount([
                    'registrations as confirmed_count' => fn ($q) => $q->where('edition', $edition)->where('status', RegistrationStatus::Confirmed),
                    'registrations as waitlisted_count' => fn ($q) => $q->where('edition', $edition)->where('status', RegistrationStatus::Waitlisted),
                    'registrations as total_count',
                ])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Territory::create($this->validated($request));

        return back()->with('status', 'Territoire ajouté.');
    }

    public function update(Request $request, Territory $territory): RedirectResponse
    {
        $territory->update($this->validated($request, $territory));

        return back()->with('status', 'Territoire mis à jour.');
    }

    public function destroy(Territory $territory): RedirectResponse
    {
        if ($territory->registrations()->exists()) {
            return back()->withErrors(['territory' => "« {$territory->city} » a des inscriptions : désactivez-le plutôt que de le supprimer."]);
        }

        $territory->delete();

        return back()->with('status', 'Territoire supprimé.');
    }

    private function validated(Request $request, ?Territory $territory = null): array
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => ['required', 'regex:/^[A-Z]{3}$/', Rule::unique('territories')->ignore($territory)],
            'city' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:80'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
        ], ['code.regex' => 'Le code doit faire exactement 3 lettres (ex. ABJ).']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
