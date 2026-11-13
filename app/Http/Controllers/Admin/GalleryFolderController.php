<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GalleryFolderService;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GalleryFolderController extends Controller
{
    public function index(GalleryFolderService $service)
    {
        return view('admin.gallery.folders', ['lots' => $service->lots()]);
    }

    public function store(Request $request, GalleryFolderService $service)
    {
        $data = $this->validated($request);
        $folder = $data['folder'];
        unset($data['folder']);
        try {
            $service->saveLot($folder, $data, true);
        } catch (\Throwable $e) {
            report($e);
            return back()->withInput()->withErrors(['folder' => $e->getMessage()]);
        }
        return back()->with('status', 'Lot créé. Déposez les photos dans images/galerie/'.$folder.'/ puis synchronisez.');
    }

    public function update(
    Request $request,
    string $folder,
    GalleryFolderService $service
) {
    $data = $this->validated($request, false);

    $request->validate([
        'apply_existing' => ['nullable', 'boolean'],
    ]);

    try {
        $service->saveLot($folder, $data, false);
    } catch (\Throwable $exception) {
        report($exception);

        return back()->withErrors([
            'folder' => 'Impossible d’enregistrer les paramètres du lot. Consultez le journal Laravel.',
        ]);
    }

    if (! $request->boolean('apply_existing')) {
        return back()->with(
            'status',
            'Lot enregistré. Les nouveaux paramètres seront utilisés pour les prochains imports uniquement.'
        );
    }

    $prefix = GalleryFolderService::BASE.'/'.$folder.'/';

    try {
        $count = GalleryPhoto::query()
            ->whereRaw(
                'LEFT(`path`, CHAR_LENGTH(?)) = ?',
                [$prefix, $prefix]
            )
            ->update([
                'edition' => $data['edition'],
                'title' => $data['title'],
                'size' => $data['size'] ?? null,
                'is_published' => $data['is_published'],
                'updated_at' => now(),
            ]);
    } catch (\Throwable $exception) {
        report($exception);

        return back()->withErrors([
            'folder' => 'Les paramètres du lot sont enregistrés, mais la mise à jour des photos a échoué. Consultez le journal puis réessayez.',
        ]);
    }

    return back()->with(
        'status',
        "Lot enregistré. {$count} ligne(s) de galerie mise(s) à jour."
    );
}

    public function sync(string $folder, GalleryFolderService $service)
    {
        try {
            $r = $service->sync($folder);
            $message = "{$r['added']} ajoutée(s), {$r['existing']} déjà présente(s), {$r['waiting']} en attente (moins de 60 secondes), {$r['ignored']} ignorée(s).";
            if ($r['limited']) {
                $message .= ' Limite de 100 ajouts atteinte : relancez pour importer la suite.';
            }
            return back()->with('status', $message);
        } catch (\Throwable $e) {
            report($e);
            return back()->withErrors(['sync' => 'Import interrompu. Certains fichiers peuvent déjà avoir été ajoutés. Consultez laravel.log puis relancez : les chemins existants sont ignorés.']);
        }
    }
public function syncAll(GalleryFolderService $service)
{
    $totals = [
        'added' => 0,
        'existing' => 0,
        'waiting' => 0,
        'ignored' => 0,
    ];

    $processed = 0;
    $failed = 0;
    $limited = 0;

    foreach ($service->lots() as $folder => $lot) {
        try {
            $result = $service->sync($folder);

            foreach (array_keys($totals) as $key) {
                $totals[$key] += $result[$key] ?? 0;
            }

            if ($result['limited'] ?? false) {
                $limited++;
            }

            $processed++;
        } catch (\Throwable $exception) {
            report($exception);
            $failed++;
        }
    }

    $message = sprintf(
        '%d dossier(s) traité(s) : %d photo(s) ajoutée(s), '
        .'%d déjà présente(s), %d en attente, %d ignorée(s).',
        $processed,
        $totals['added'],
        $totals['existing'],
        $totals['waiting'],
        $totals['ignored']
    );

    if ($limited > 0) {
        $message .= " {$limited} dossier(s) ont encore des fichiers à examiner : relancez la synchronisation.";
    }

    if ($failed > 0) {
        $message .= " {$failed} dossier(s) en erreur : consultez laravel.log.";
    }

    return back()->with('status', $message);
}

    private function validated(Request $request, bool $create = true): array
    {
        $rules = [
            'edition' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:150'],
            'size' => ['nullable', Rule::in(['wide', 'tall'])],
        ];
        if ($create) {
            $rules['folder'] = ['required', 'string', 'max:40', 'regex:/\A[a-z0-9][a-z0-9_-]{0,39}\z/'];
        }
        $data = $request->validate($rules);
        $data['is_published'] = $request->boolean('is_published');
        $data['auto_sync'] = $request->boolean('auto_sync');
        return $data;
    }
}
