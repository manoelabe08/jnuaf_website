<?php



namespace App\Http\Controllers\Admin;



use App\Http\Controllers\Controller;

use App\Models\SecurityEvent;

use App\Models\SponsorLogo;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Illuminate\Validation\Rule;

use Illuminate\View\View;



class SponsorLogoController extends Controller

{

    public function index(): View

    {

        return view('admin.sponsors.index', [

            'logos' => SponsorLogo::orderBy('position')->orderBy('name')->get(),

            'categories' => SponsorLogo::CATEGORIES,

        ]);

    }



    public function store(Request $request): RedirectResponse

    {

        $data = $this->validated($request, true);

        $file = $this->storeFile($request->file('logo'));

        $logo = SponsorLogo::create($data + $file + ['is_active' => true]);

        $this->audit('Logo ajouté', $logo, $request);



        return back()->with('status', 'Logo ajouté au bandeau.');

    }



    public function update(Request $request, SponsorLogo $logo): RedirectResponse

    {

        $data = $this->validated($request, false);

        $data['is_active'] = $request->boolean('is_active');



        $oldFile = null;

        if ($request->hasFile('logo')) {

            $oldFile = $logo->path;

            $data += $this->storeFile($request->file('logo'));

        }



        $logo->update($data);

        if ($oldFile) {

            Storage::disk('local')->delete('sponsors/'.$oldFile);

        }

        $this->audit('Logo modifié', $logo, $request);



        return back()->with('status', 'Logo mis à jour.');

    }



    public function destroy(Request $request, SponsorLogo $logo): RedirectResponse

    {

        $this->audit('Logo supprimé', $logo, $request);

        $logo->delete();



        return back()->with('status', 'Logo supprimé.');

    }



    private function validated(Request $request, bool $logoRequired): array

    {

        $data = $request->validate([

            'name' => ['required', 'string', 'min:2', 'max:120'],

            'url' => ['nullable', 'url:http,https', 'max:255'],

            'category' => ['required', Rule::in(array_keys(SponsorLogo::CATEGORIES))],

            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],

            'logo' => [$logoRequired ? 'required' : 'nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],

        ], [

            'logo.required' => "Ajoutez le fichier du logo.",

            'logo.image' => "Le fichier n'est pas une image valide.",

            'logo.mimes' => 'Le logo doit être au format PNG, JPG ou WebP.',

            'logo.max' => 'Le logo ne doit pas dépasser 2 Mo.',

            'url.url' => "L'adresse du site doit commencer par http:// ou https://.",

        ]);



        unset($data['logo']);

        $data['position'] = (int) ($data['position'] ?? 0);



        return $data;

    }



    private function storeFile(UploadedFile $file): array

    {

        $ext = match ((string) $file->getMimeType()) {

            'image/png' => 'png',

            'image/webp' => 'webp',

            default => 'jpg',

        };

        $mime = ['png' => 'image/png', 'webp' => 'image/webp', 'jpg' => 'image/jpeg'][$ext];

        $name = Str::lower(Str::random(24)).'.'.$ext;

        Storage::disk('local')->putFileAs('sponsors', $file, $name);



        return ['path' => $name, 'mime' => $mime];

    }



    private function audit(string $action, SponsorLogo $logo, Request $request): void

    {

        SecurityEvent::create([

            'user_id' => $request->user()?->id,

            'action' => 'logos : '.$action,

            'details' => ['Nom' => $logo->name, 'Catégorie' => SponsorLogo::CATEGORIES[$logo->category] ?? $logo->category],

            'ip' => $request->ip(),

            'user_agent' => substr((string) $request->userAgent(), 0, 500),

        ]);

    }

}

