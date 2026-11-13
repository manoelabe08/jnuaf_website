<?php



namespace App\Http\Requests;



use App\Models\Event;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Rule;



class StoreEventRegistrationRequest extends FormRequest

{

    private ?Event $eventCache = null;



    public function eventModel(): Event

    {

        return $this->eventCache ??= Event::where('slug', (string) $this->route('slug'))->where('is_published', true)->where('type', '!=', 'jnuaf')->firstOrFail();

    }



    public function authorize(): bool

    {

        $event = $this->eventModel();

        $end = $event->ends_at ?? $event->starts_at;



        return (bool) $event->registration_open && (! $end || $end->isFuture());

    }



    protected function failedAuthorization(): void

    {

        abort(403, "Les inscriptions \u{e0} cet \u{e9}v\u{e9}nement sont ferm\u{e9}es.");

    }



    protected function getRedirectUrl(): string

    {

        return route('events.show', (string) $this->route('slug'));

    }



    protected function prepareForValidation(): void

    {

        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);

    }



    public function rules(): array

    {

        $event = $this->eventModel();



        return [

            'last_name' => ['required', 'string', 'min:2', 'max:80'],

            'first_name' => ['required', 'string', 'min:2', 'max:80'],

            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('registrations', 'email')->where('edition', 'evt-' . $event->id)],

            'phone' => ['required', 'regex:/^\+?[0-9 .\-]{8,20}$/'],

            'city' => ['required', 'string', 'min:2', 'max:80'],

            'territory_id' => $event->territory_id ? ['nullable'] : ['required', Rule::exists('territories', 'id')->where('is_active', true)],

            'consent_data' => ['accepted'],

            'website' => ['prohibited'],

        ];

    }



    public function messages(): array

    {

        return [

            'email.unique' => "Une inscription existe d\u{e9}j\u{e0} pour cet email \u{e0} cet \u{e9}v\u{e9}nement.",

            'phone.regex' => "Num\u{e9}ro invalide, indiquez l'indicatif, ex. +225...",

            'consent_data.accepted' => "Cochez la case pour autoriser le traitement de vos donn\u{e9}es.",

        ];

    }



    public function attributes(): array

    {

        return ['last_name' => 'nom', 'first_name' => "pr\u{e9}nom", 'phone' => "t\u{e9}l\u{e9}phone", 'territory_id' => 'territoire', 'city' => 'ville'];

    }

}

