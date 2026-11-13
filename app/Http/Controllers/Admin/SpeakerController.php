<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Speaker;
use App\Models\Territory;
use App\Services\SpeakerPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SpeakerController extends Controller
{
    public function index(): View
    {
        return view('admin.speakers.index', [
            'speakers' => Speaker::with('territory')->orderBy('position')->get(),
            'territories' => Territory::active()->get(),
        ]);
    }

    public function store(Request $request, SpeakerPhotoService $photos): RedirectResponse
    {
        $data = $this->validated($request);
        $newPath = null;
        try {
            DB::transaction(function () use ($request, $data, $photos, &$newPath) {
                $speaker = Speaker::create($data + ['photo_path' => null]);
                if ($request->hasFile('photo')) {
                    $newPath = $photos->save($request->file('photo'), $speaker);
                    $speaker->update(['photo_path' => $newPath]);
                }
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                $photos->removeOriginal(public_path($newPath));
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            return back()->withInput()->withErrors([
                'photo' => 'Ajout impossible. Consultez le journal Laravel et les droits du dossier images/speakers.',
            ]);
        }
        return back()->with('status', 'Intervenante ajoutée.');
    }

    public function update(Request $request, Speaker $speaker, SpeakerPhotoService $photos): RedirectResponse
    {
        $data = $this->validated($request);
        $newPath = null;
        $original = null;
        try {
            if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
                if ($request->hasFile('photo')) {
                    $naming = clone $speaker;
                    $naming->name = $data['name'];
                    $newPath = $photos->save($request->file('photo'), $naming);
                }
                $original = $photos->backup($speaker->photo_path);
                $data['photo_path'] = $newPath;
            }
            $speaker->update($data);
        } catch (\Throwable $exception) {
            if ($newPath) {
                $photos->removeOriginal(public_path($newPath));
            }
            if ($exception instanceof ValidationException) {
                throw $exception;
            }
            report($exception);
            return back()->withInput()->withErrors([
                'photo' => 'Modification interrompue : impossible d’enregistrer ou d’archiver la photo. Consultez le journal Laravel.',
            ]);
        }
        $photos->removeOriginal($original);
        return back()->with('status', 'Fiche mise à jour. Ancienne photo archivée si elle existait.');
    }

    public function destroy(Speaker $speaker): RedirectResponse
    {
        try {
            $speaker->delete();
        } catch (\Throwable $exception) {
            report($exception);
            return back()->with('status', 'Suppression interrompue. Vérifiez le dossier d’archives et le journal Laravel.');
        }
        return back()->with('status', 'Fiche supprimée. Photo archivée si elle existait.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'session' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:1500'],
            'territory_id' => ['nullable', Rule::exists('territories', 'id')],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        return collect($data)->except(['photo', 'remove_photo'])->all();
    }
}
