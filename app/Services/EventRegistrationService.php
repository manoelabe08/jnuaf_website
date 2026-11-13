<?php



namespace App\Services;



use App\Enums\Profile;

use App\Enums\RegistrationStatus;

use App\Mail\RegistrationWaitlisted;

use App\Models\Event;

use App\Models\Registration;

use App\Models\Territory;

use Illuminate\Support\Arr;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;



class EventRegistrationService

{

    /** @return array{0: Registration, 1: string} */

    public function register(Event $event, array $data): array

    {

        $manageToken = Str::random(48);



        $registration = DB::transaction(function () use ($event, $data, $manageToken) {

            $ev = Event::lockForUpdate()->findOrFail($event->id);

            $territory = Territory::findOrFail($ev->territory_id ?: (int) ($data['territory_id'] ?? 0));

            $taken = Registration::where('event_id', $ev->id)->where('status', RegistrationStatus::Confirmed)->count();

            $full = $ev->capacity !== null && $taken >= $ev->capacity;



            return Registration::create([

                ...Arr::only($data, ['last_name', 'first_name', 'phone', 'city']),

                'email' => Str::lower(trim($data['email'])),

                'territory_id' => $territory->id,

                'edition' => 'evt-' . $ev->id,

                'event_id' => $ev->id,

                'profile' => Profile::Participante,

                'status' => $full ? RegistrationStatus::Waitlisted : RegistrationStatus::Confirmed,

                'consent_data' => true,

                'consent_image' => false,

                'consented_at' => now(),

                'badge_code' => $this->uniqueBadgeCode($ev, $territory),

                'qr_token' => Str::random(40),

                'manage_token_hash' => Registration::hashToken($manageToken),

            ]);

        });



        if ($registration->status === RegistrationStatus::Waitlisted) {

            Mail::to($registration->email)->send(new RegistrationWaitlisted($registration, $manageToken));

        } else {

            app(RegistrationService::class)->sendConfirmation($registration, $manageToken);

        }



        return [$registration, $manageToken];

    }



    private function uniqueBadgeCode(Event $event, Territory $territory): string

    {

        do {

            $code = sprintf('EVT%s-%s-%s', $event->id, $territory->code, Str::upper(Str::random(6)));

        } while (Registration::where('badge_code', $code)->exists());



        return $code;

    }

}

