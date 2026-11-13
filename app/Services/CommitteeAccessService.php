<?php



namespace App\Services;



use App\Models\SecurityEvent;

use App\Models\User;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class CommitteeAccessService

{

    public function revoke(User $user, string $reason): void

    {

        if ($user->access_source !== 'committee' || $user->suspended_at) {

            return;

        }



        DB::transaction(function () use ($user) {

            $user->forceFill(['suspended_at' => now(), 'remember_token' => Str::random(60)])->save();

            app(SessionService::class)->endAll($user);

        });



        try {

            SecurityEvent::create([

                'user_id' => $user->id,

                'action' => 'Acces comite retire',

                'details' => ['Motif' => $reason, 'Candidature' => (string) $user->committee_application_id],

                'ip' => 'systeme',

                'user_agent' => 'systeme',

            ]);

        } catch (\Throwable $e) {

            report($e);

        }



        app(SensitiveActionService::class)->notify($user, 'Acces comite retire (compte suspendu)', ['Motif' => $reason]);

    }



    public function syncApplication(int $applicationId): void

    {

        $users = User::where('access_source', 'committee')

            ->where('committee_application_id', $applicationId)

            ->whereNull('suspended_at')

            ->get();



        foreach ($users as $user) {

            if (! $user->committeeAccessValid()) {

                $this->revoke($user, 'candidature modifiee, refusee ou supprimee');

            }

        }

    }



    public function sync(): int

    {

        $count = 0;

        $users = User::where('access_source', 'committee')->whereNull('suspended_at')->get();



        foreach ($users as $user) {

            if (! $user->committeeAccessValid()) {

                $this->revoke($user, 'controle periodique : candidature absente ou grade retire');

                $count++;

            }

        }



        return $count;

    }

}

