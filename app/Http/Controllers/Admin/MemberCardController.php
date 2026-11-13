<?php



namespace App\Http\Controllers\Admin;



use App\Enums\ApplicationStatus;
use App\Mail\MemberCardMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

use App\Http\Controllers\Controller;

use App\Models\CommitteeApplication;

use App\Models\MemberCard;

use App\Models\Registration;

use App\Models\Territory;

use App\Models\User;

use App\Services\MemberCardService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\View\View;



class MemberCardController extends Controller

{

    public function index(Request $request): View

    {

        $this->guard($request);



        $q = trim((string) $request->query('q', ''));

        $category = (string) $request->query('categorie', '');

        $like = '%'.addcslashes($q, '%_\\').'%';



        $cards = MemberCard::query()

            ->with('territory')

            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($like) {

                $where->where('full_name', 'like', $like)->orWhere('number', 'like', $like);

            }))

            ->when(

                array_key_exists($category, MemberCardService::CATEGORIES),

                fn ($query) => $query->where('category', $category)

            )

            ->orderByDesc('id')

            ->simplePaginate(20)

            ->withQueryString();



        return view('admin.cards.index', compact('cards', 'q', 'category'));

    }



    public function create(Request $request): View

    {

        $this->guard($request);



        return view('admin.cards.create', [

            'candidates' => $this->candidates(),

            'territories' => Territory::query()->orderBy('position')->get(),

        ]);

    }



    public function store(Request $request, MemberCardService $cards): RedirectResponse

    {

        $this->guard($request);



        $data = $request->validate([

            'person' => ['required', 'string', 'max:40'],

            'territory_id' => ['nullable', 'integer', 'exists:territories,id'],

            'role_label' => ['nullable', 'string', 'max:120'],

            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096', 'dimensions:min_width=200,min_height=250'],

        ], [

            'person.required' => 'Choisissez une personne.',

            'photo.image' => 'Le fichier doit être une image.',

            'photo.mimes' => 'Photo au format JPG ou PNG uniquement.',

            'photo.max' => 'La photo ne doit pas dépasser 4 Mo.',

            'photo.dimensions' => 'La photo est trop petite (200 x 250 pixels au minimum).',

        ]);



        $candidate = collect($this->candidates())->firstWhere('key', $data['person']);



        if ($candidate === null) {

            return back()->withInput()->withErrors(['person' => 'Personne introuvable.']);

        }



        if ($candidate['issued']) {

            return back()->withInput()->withErrors(['person' => 'Une carte valide existe déjà pour cette personne.']);

        }



        $territoryId = $request->filled('territory_id') ? (int) $data['territory_id'] : null;



        if ($candidate['category'] !== 'BP' && $territoryId === null) {

            return back()->withInput()->withErrors(['territory_id' => 'Choisissez un territoire pour cette catégorie.']);

        }



        try {

            $card = $cards->issue([

                'source_type' => $candidate['source_type'],

                'source_id' => $candidate['source_id'],

                'category' => $candidate['category'],

                'territory_id' => $territoryId,

                'full_name' => $candidate['name'],

                'email' => $candidate['email'],

                'role_label' => trim((string) ($data['role_label'] ?? '')) ?: $candidate['role'],

            ], $request->user(), $request->file('photo') ?? $this->storedPhoto($candidate));

        } catch (\Throwable $exception) {

            report($exception);



            return back()->withInput()->withErrors([

                'person' => 'La carte n\'a pas pu être émise. Réessayez, ou consultez le journal du serveur.',

            ]);

        }



        return redirect()

            ->route('admin.cards.index')

            ->with('status', 'Carte '.$card->number.' émise pour '.$card->full_name.'.');

    }



    public function pdf(Request $request, MemberCard $card, MemberCardService $cards): Response

    {

        $this->guard($request);



        return response($cards->pdfFor($card), 200, [

            'Content-Type' => 'application/pdf',

            'Content-Disposition' => 'inline; filename="'.$cards->filename($card).'"',

        ]);

    }




    public function photo(Request $request, MemberCard $card, MemberCardService $cards): RedirectResponse

    {

        $this->guard($request);



        $request->validate([

            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:4096', 'dimensions:min_width=200,min_height=250'],

        ], [

            'photo.required' => 'Sélectionnez une photo.',

            'photo.image' => 'Le fichier doit être une image.',

            'photo.mimes' => 'Photo au format JPG ou PNG uniquement.',

            'photo.max' => 'La photo ne doit pas dépasser 4 Mo.',

            'photo.dimensions' => 'La photo est trop petite (200 x 250 pixels au minimum).',

        ]);



        try {

            $cards->replacePhoto($card, $request->file('photo'));

        } catch (\Throwable $exception) {

            report($exception);



            return back()->withErrors(['photo' => 'La photo n\'a pas pu être enregistrée.']);

        }



        return redirect()

            ->route('admin.cards.index')

            ->with('status', 'Photo enregistrée pour '.$card->full_name.'.');

    }



    public function sendCard(Request $request, MemberCard $card, MemberCardService $cards): RedirectResponse

    {

        $this->guard($request);



        if (! $card->isActive()) {

            return back()->withErrors(['send' => 'Cette carte n\'est plus valide : elle ne peut pas être envoyée.']);

        }



        if (! filter_var((string) $card->email, FILTER_VALIDATE_EMAIL)) {

            return back()->withErrors(['send' => 'Aucune adresse email valide pour ce titulaire.']);

        }



        try {

            Mail::mailer('team')

                ->to($card->email)

                ->send(new MemberCardMessage($card, $cards->pdfFor($card), $cards->filename($card)));

        } catch (\Throwable $exception) {

            report($exception);



            return back()->withErrors(['send' => 'L\'envoi a échoué. Aucun message n\'a été envoyé.']);

        }



        $card->forceFill(['sent_at' => now()])->save();



        Log::info('Carte de membre envoyée', [

            'user_id' => $request->user()?->id,

            'card' => $card->number,

        ]);



        return redirect()

            ->route('admin.cards.index')

            ->with('status', 'Carte '.$card->number.' envoyée à '.$card->email.'.');

    }



    public function renew(Request $request, MemberCard $card, MemberCardService $cards): RedirectResponse

    {

        $this->guard($request);



        if ($card->isRevoked() || $card->anonymized_at !== null) {

            return back()->withErrors(['send' => 'Une carte révoquée ne peut pas être renouvelée.']);

        }



        $start = $card->valid_until->copy()->max(now()->startOfDay());

        $newDate = $start->addYears($cards->validityYears($card->category));



        $card->forceFill(['valid_until' => $newDate->toDateString()])->save();



        return redirect()

            ->route('admin.cards.index')

            ->with('status', 'Carte '.$card->number.' renouvelée jusqu\'au '.$newDate->format('d/m/Y').'. Pensez à renvoyer la carte au titulaire.');

    }


    public function revoke(Request $request, MemberCard $card): RedirectResponse

    {

        $this->guard($request);



        if (! $card->isRevoked()) {

            $card->forceFill(['revoked_at' => now()])->save();

        }



        return redirect()

            ->route('admin.cards.index')

            ->with('status', 'Carte '.$card->number.' révoquée.');

    }




    private function storedPhoto(array $candidate): ?\Illuminate\Http\UploadedFile

    {

        try {

            $svc = app(\App\Services\PhotoCollectionService::class);

            $file = null;

            if ($candidate['source_type'] === 'committee') {

                $name = (string) CommitteeApplication::whereKey($candidate['source_id'])->value('photo_path');

                if (preg_match('/^\d+-[a-z0-9]{24}\.jpg$/', $name) === 1) {

                    $file = $svc->committeeRoot().'/'.$name;

                }

            } elseif ($candidate['source_type'] === 'registration') {

                $name = (string) Registration::whereKey($candidate['source_id'])->value('photo_path');

                if (preg_match('/^\d+-[a-z0-9]{24}\.jpg$/', $name) === 1) {

                    $file = $svc->registrationRoot().'/'.$name;

                }

            }

            if ($file === null || ! is_file($file)) {

                return null;

            }

            $tmp = tempnam(sys_get_temp_dir(), 'jnuaf-card-');

            copy($file, $tmp);

            register_shutdown_function(static function () use ($tmp) {

                if (is_file($tmp)) {

                    @unlink($tmp);

                }

            });



            return new \Illuminate\Http\UploadedFile($tmp, 'photo.jpg', 'image/jpeg', null, true);

        } catch (\Throwable $exception) {

            report($exception);



            return null;

        }

    }

    private function guard(Request $request): void

    {

        abort_unless($request->user()?->isAdmin(), 403);

    }



    /** @return array<int, array<string, mixed>> */

    private function candidates(): array

    {

        $taken = MemberCard::query()

            ->whereNull('revoked_at')

            ->whereDate('valid_until', '>=', now()->toDateString())

            ->get(['source_type', 'source_id'])

            ->map(fn ($card) => $card->source_type.':'.$card->source_id)

            ->all();



        $list = [];



        foreach (User::query()->with('territory')->orderBy('name')->get() as $user) {

            $isAdmin = $user->isAdmin();

            $key = 'user:'.$user->id;



            $list[] = [

                'key' => $key,

                'group' => $isAdmin ? 'Bureau de Pilotage' : 'Points focaux',

                'label' => $user->name.' — '.$user->email,

                'name' => $user->name,

                'email' => $user->email,

                'category' => $isAdmin ? 'BP' : 'PF',

                'territory_id' => $isAdmin ? null : $user->territory_id,

                'role' => $isAdmin

                    ? 'Bureau de Pilotage'

                    : 'Point focal'.($user->territory ? ' · '.$user->territory->city : ''),

                'source_type' => 'user',

                'source_id' => $user->id,

                'issued' => in_array($key, $taken, true),

            ];

        }



        $applications = CommitteeApplication::query()

            ->where('status', ApplicationStatus::Accepted)

            ->orderBy('full_name')

            ->get();



        foreach ($applications as $application) {

            $key = 'committee:'.$application->id;



            $list[] = [

                'key' => $key,

                'group' => 'Comité d\'organisation (candidatures retenues)',

                'label' => $application->full_name.' — '.$application->email.($application->photo_path ? ' (photo fournie)' : ''),

                'name' => $application->full_name,

                'email' => $application->email,

                'category' => 'CO',

                'territory_id' => null,

                'role' => 'Membre du comité d\'organisation'.($application->pole ? ' · '.$application->pole : ''),

                'source_type' => 'committee',

                'source_id' => $application->id,

                'issued' => in_array($key, $taken, true),

            ];

        }



        $registrations = Registration::currentEdition()

            ->with('territory')

            ->whereIn('profile', ['mentor', 'intervenante'])

            ->whereNull('anonymized_at')

            ->orderBy('last_name')

            ->get()

            ->reject(fn ($registration) => $registration->isCancelled());



        foreach ($registrations as $registration) {

            $key = 'registration:'.$registration->id;



            $list[] = [

                'key' => $key,

                'group' => 'Intervenantes et mentors',

                'label' => $registration->fullName().' — '.$registration->profile->label().' — '.$registration->territory->city.($registration->photo_path ? ' (photo fournie)' : ''),

                'name' => $registration->fullName(),

                'email' => $registration->email,

                'category' => 'IM',

                'territory_id' => $registration->territory_id,

                'role' => $registration->profile->label(),

                'source_type' => 'registration',

                'source_id' => $registration->id,

                'issued' => in_array($key, $taken, true),

            ];

        }



        return $list;

    }

}

