<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GalleryController extends Controller
{
    private const UPLOAD_DIR = 'images/FileWithAdminWebsite';

    public function index(): View
    {
        return view('admin.gallery.index', [
            'photos' => GalleryPhoto::orderByDesc('edition')->orderBy('position')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['path'] = $request->hasFile('photo') ? $this->saveUpload($request->file('photo')) : null;
        GalleryPhoto::create($data);
        return back()->with('status', 'Photo ajoutée.');
    }

    public function update(Request $request, GalleryPhoto $photo): RedirectResponse
    {
        $data = $this->validated($request);
        $newPath = null;
        if ($request->hasFile('photo')) {
            $newPath = $this->saveUpload($request->file('photo'));
            try {
                // Sauvegarde préalable vérifiée : l’original reste en place jusqu’à la mise à jour en base.
                $this->copyBackup($photo->path);
            } catch (\Throwable $e) {
                report($e);
                return back()->with('status', 'Remplacement interrompu : archivage impossible. La nouvelle image peut rester non référencée ; consultez le journal.');
            }
            $data['path'] = $newPath;
        }
        $oldPath = $photo->path;
        $photo->update($data);
        if ($newPath && $oldPath !== $newPath) {
            try {
                $this->archiveFile($oldPath);
            } catch (\Throwable $e) {
                report($e);
                return back()->with('status', 'Photo remplacée et copie archivée, mais ancien fichier encore présent. Retirez-le du dossier surveillé pour éviter une réimportation.');
            }
        }
        return back()->with('status', 'Photo mise à jour.');
    }

    public function destroy(GalleryPhoto $photo): RedirectResponse
    {
        // Sans cette étape, un fichier laissé dans le dossier surveillé serait réimporté.
        try {
            $this->archiveFile($photo->path);
        } catch (\Throwable $e) {
            report($e);
            return back()->with('status', 'Suppression interrompue : archivage impossible, consultez le journal.');
        }
        $photo->delete();
        return back()->with('status', 'Photo supprimée.');
    }

    public function bulk(Request $request): RedirectResponse

    {

        $data = $request->validate([

            'op' => ['required', 'in:publish,hide,delete'],

            'ids' => ['required', 'array', 'min:1', 'max:50'],

            'ids.*' => ['integer'],

        ]);

        $done = 0;

        $failed = 0;

        foreach (GalleryPhoto::whereIn('id', array_unique($data['ids']))->get() as $photo) {

            if ($data['op'] === 'delete') {

                try {

                    $this->archiveFile($photo->path);

                } catch (\Throwable $e) {

                    report($e);

                    $failed++;

                    continue;

                }

                $photo->delete();

            } else {

                $photo->update(['is_published' => $data['op'] === 'publish']);

            }

            $done++;

        }

        \Illuminate\Support\Facades\Log::info('Galerie : action en masse', ['operation' => $data['op'], 'ids' => array_values(array_unique($data['ids'])), 'traitees' => $done, 'echecs' => $failed, 'acteur_id' => $request->user()->id]);

        $msg = $done.' photo(s) traitee(s)';

        if ($failed) {

            $msg .= ', '.$failed.' non supprimee(s) (archivage impossible, voir le journal)';

        }

        return back()->with('status', $msg.'.');

    }

    private function saveUpload(UploadedFile $file): string
    {
        $dir = public_path(self::UPLOAD_DIR);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Impossible de créer le dossier des photos.');
        }
        do {
            $name = Str::lower(Str::random(8)).'.'.$file->extension();
        } while (file_exists($dir.'/'.$name));
        $file->move($dir, $name);
        return self::UPLOAD_DIR.'/'.$name;
    }

    private function copyBackup(?string $path): void
    {
        if (! $path) {
            return;
        }
        if (str_starts_with($path, self::UPLOAD_DIR.'/') || str_starts_with($path, 'images/galerie/')) {
            $source = public_path($path);
            $base = public_path(str_starts_with($path, 'images/galerie/') ? 'images/galerie' : self::UPLOAD_DIR);
            if (is_file($source) && (! realpath($base) || ! str_starts_with(realpath($source), realpath($base).DIRECTORY_SEPARATOR))) {
                throw new \RuntimeException('Chemin de photo non autorisé.');
            }
        } elseif (! str_starts_with($path, 'images/')) {
            $source = Storage::disk('public')->path($path);
        } else {
            return;
        }
        if (! is_file($source)) {
            return;
        }
        $trash = storage_path('app/old_as_delete');
        if (! is_dir($trash) && ! mkdir($trash, 0755, true) && ! is_dir($trash)) {
            throw new \RuntimeException('Impossible de créer les archives.');
        }
        $copy = $trash.'/'.date('Ymd-His').'-backup-'.Str::lower(Str::random(8)).'-'.basename($source);
        if (! copy($source, $copy) || hash_file('sha256', $source) !== hash_file('sha256', $copy)) {
            throw new \RuntimeException('Copie d’archive impossible ou non vérifiée.');
        }
    }

    private function archiveFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (str_starts_with($path, self::UPLOAD_DIR.'/') || str_starts_with($path, 'images/galerie/')) {
            $base = str_starts_with($path, 'images/galerie/')
                ? public_path('images/galerie') : public_path(self::UPLOAD_DIR);
            $source = public_path($path);
            if (is_file($source)) {
                $realBase = realpath($base);
                $realSource = realpath($source);
                if (! $realBase || ! $realSource || ! str_starts_with($realSource, $realBase.DIRECTORY_SEPARATOR)) {
                    throw new \RuntimeException('Chemin de photo non autorisé.');
                }
            }
        } elseif (! str_starts_with($path, 'images/')) {
            $source = Storage::disk('public')->path($path);
        } else {
            return;
        }

        if (! is_file($source)) {
            return;
        }
        $trash = storage_path('app/old_as_delete');
        if (! is_dir($trash) && ! mkdir($trash, 0755, true) && ! is_dir($trash)) {
            throw new \RuntimeException('Impossible de créer le dossier d’archives.');
        }
        $destination = $trash.'/'.date('Ymd-His').'-'.Str::lower(Str::random(8)).'-'.basename($source);
        if (! rename($source, $destination)) {
            throw new \RuntimeException('Archivage impossible : opération interrompue.');
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'edition' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:150'],
            'size' => ['nullable', Rule::in(['wide', 'tall'])],
            'position' => ['required', 'integer', 'min:0', 'max:999'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $data['is_published'] = $request->boolean('is_published');
        return collect($data)->except('photo')->all();
    }
}
