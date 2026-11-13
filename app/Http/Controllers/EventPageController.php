<?php



namespace App\Http\Controllers;



use App\Enums\RegistrationStatus;

use App\Http\Requests\StoreEventRegistrationRequest;

use App\Models\Event;

use App\Models\Registration;

use App\Models\Territory;

use App\Services\EventRegistrationService;

use Illuminate\Http\RedirectResponse;

use Illuminate\Contracts\View\View;



class EventPageController extends Controller

{

    public function show(string $slug): View|RedirectResponse

    {

        $event = Event::with('territory')->where('slug', $slug)->where('is_published', true)->firstOrFail();



        if ($event->type === 'jnuaf') {

            return redirect(route('home', array_filter(['territoire' => $event->territory?->code])) . '#inscription');

        }



        $end = $event->ends_at ?? $event->starts_at;

        $closed = ! $event->registration_open || ($end && $end->isPast());

        $taken = Registration::where('event_id', $event->id)->where('status', RegistrationStatus::Confirmed)->count();

        $remaining = $event->capacity === null ? null : max(0, $event->capacity - $taken);

        $territories = $event->territory_id ? collect() : Territory::active()->get();



        return view('events.show', compact('event', 'closed', 'remaining', 'territories'));

    }



    public function store(StoreEventRegistrationRequest $request, EventRegistrationService $service): RedirectResponse

    {

        $event = $request->eventModel();

        [$registration] = $service->register($event, $request->validated());



        return redirect()->route('events.show', $event->slug)->with('registration', [

            'first_name' => $registration->first_name,

            'email' => $registration->email,

            'waitlisted' => $registration->status === RegistrationStatus::Waitlisted,

        ]);

    }

}

