<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Event;

use App\Models\Territory;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\Support\Str;

use Illuminate\Validation\Rule;

use Illuminate\View\View;

class EventController extends Controller

{

    private function authorizeAdmin(Request $request): void

    {

        abort_unless($request->user()->isAdmin(), 403);

    }

    private function territories()

    {

        return Territory::where('is_active', true)->orderBy('position')->get();

    }

    public function index(Request $request): View

    {

        $this->authorizeAdmin($request);

        return view('admin.events.index', ['events' => Event::with('territory')->withCount('registrations')->orderByDesc('starts_at')->get()]);

    }

    public function create(Request $request): View

    {

        $this->authorizeAdmin($request);

        return view('admin.events.form', ['event' => new Event(['type' => 'formation', 'registration_open' => true]), 'territories' => $this->territories()]);

    }

    public function store(Request $request): RedirectResponse

    {

        $this->authorizeAdmin($request);

        $data = $this->validated($request);

        $data['slug'] = $this->uniqueSlug($data['title']);

        $event = Event::create($data);

        return redirect()->route('admin.events.index')->with('status', "\u{c9}v\u{e9}nement cr\u{e9}\u{e9} : " . $event->title . '.');

    }

    public function edit(Request $request, Event $event): View

    {

        $this->authorizeAdmin($request);

        return view('admin.events.form', ['event' => $event, 'territories' => $this->territories()]);

    }

    public function update(Request $request, Event $event): RedirectResponse

    {

        $this->authorizeAdmin($request);

        $event->update($this->validated($request));

        return redirect()->route('admin.events.index')->with('status', "\u{c9}v\u{e9}nement mis \u{e0} jour : " . $event->title . '.');

    }

    public function destroy(Request $request, Event $event): RedirectResponse

    {

        $this->authorizeAdmin($request);

        abort_if($event->type === 'jnuaf' || $event->registrations()->exists(), 422, "Cet \u{e9}v\u{e9}nement ne peut pas \u{ea}tre supprim\u{e9} (type JNUAF ou inscriptions existantes).");

        $event->delete();

        return redirect()->route('admin.events.index')->with('status', "\u{c9}v\u{e9}nement supprim\u{e9}.");

    }

    private function validated(Request $request): array

    {

        $data = $request->validate([

            'title' => ['required', 'string', 'max:150'],

            'type' => ['required', Rule::in(array_keys(Event::TYPES))],

            'territory_id' => ['nullable', 'integer', 'exists:territories,id'],

            'location' => ['nullable', 'string', 'max:190'],

            'starts_at' => ['required', 'date'],

            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],

            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],

            'description' => ['nullable', 'string', 'max:2000'],

        ]);

        $data['registration_open'] = $request->boolean('registration_open');

        $data['is_published'] = $request->boolean('is_published');

        return $data;

    }

    private function uniqueSlug(string $title): string

    {

        $base = Str::slug($title) ?: 'evenement';

        $slug = $base;

        $i = 2;

        while (Event::where('slug', $slug)->exists()) {

            $slug = $base . '-' . $i++;

        }

        return $slug;

    }

}

