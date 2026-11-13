<?php



namespace App\Services;



use App\Enums\RegistrationStatus;

use App\Models\Event;

use App\Models\Registration;

use Illuminate\Support\Facades\DB;



class EventWaitlistService

{

    /** Une place s'est lib\u00e9r\u00e9e : la premi\u00e8re personne en liste d'attente de l'\u00e9v\u00e9nement est confirm\u00e9e. */

    public function promoteNext(?Event $event): ?Registration

    {

        if (! $event) {

            return null;

        }



        $next = DB::transaction(function () use ($event) {

            $ev = Event::lockForUpdate()->find($event->id);



            if (! $ev) {

                return null;

            }



            $taken = Registration::where('event_id', $ev->id)->where('status', RegistrationStatus::Confirmed)->count();



            if ($ev->capacity !== null && $taken >= $ev->capacity) {

                return null;

            }



            $next = Registration::where('event_id', $ev->id)->where('status', RegistrationStatus::Waitlisted)->oldest('id')->lockForUpdate()->first();

            $next?->update(['status' => RegistrationStatus::Confirmed]);



            return $next;

        });



        if ($next) {

            app(RegistrationService::class)->sendConfirmation($next, promoted: true);

        }



        return $next;

    }

}

