<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramItem;
use App\Models\Territory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        // territory vide = programme type commun.
        $territoryId = $request->integer('territory') ?: null;

        return view('admin.program.index', [
            'items' => ProgramItem::where('territory_id', $territoryId)->orderBy('position')->get(),
            'territories' => Territory::active()->get(),
            'territoryId' => $territoryId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProgramItem::create($this->validated($request));

        return back()->with('status', 'Créneau ajouté.');
    }

    public function update(Request $request, ProgramItem $item): RedirectResponse
    {
        $item->update($this->validated($request));

        return back()->with('status', 'Créneau mis à jour.');
    }

    public function destroy(ProgramItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('status', 'Créneau supprimé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'territory_id' => ['nullable', Rule::exists('territories', 'id')],
            'slot' => ['required', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'kind' => ['required', 'string', 'max:30'],
            'room' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
