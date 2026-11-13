<?php



namespace App\Services;



use App\Enums\ApplicationStatus;

use App\Mail\AccountCreated;

use App\Models\CommitteeApplication;

use App\Models\Territory;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Facades\Password;

use Illuminate\Support\Str;



class CommitteeAccountService

{

    public bool $mailSent = false;



    public function create(CommitteeApplication $application, User $actor, ?Request $request = null): User

    {

        if ($application->status !== ApplicationStatus::Accepted) {

            throw new \DomainException('Seules les candidatures retenues peuvent recevoir un compte.');

        }

        if (! in_array((string) $application->grade, (array) config('jnuaf_access.grades', []), true)) {

            throw new \DomainException("Ce grade n'ouvre aucun acces.");

        }

        if (! $application->territory_id) {

            throw new \DomainException("Choisissez d'abord une ville pour cette candidature.");

        }

        $territory = Territory::find($application->territory_id);

        if (! $territory) {

            throw new \DomainException('La ville choisie est introuvable.');

        }



        $email = mb_strtolower(trim((string) $application->email));

        if (User::where('committee_application_id', $application->id)->exists()

            || User::whereRaw('LOWER(email) = ?', [$email])->exists()) {

            throw new \DomainException('Un compte existe deja pour cette personne.');

        }



        $isCountryLead = (string) $application->grade === 'country_lead';

        if ($isCountryLead && DB::table('user_scopes')

            ->join('users', 'users.id', '=', 'user_scopes.user_id')

            ->where('user_scopes.scope_type', 'country')

            ->where('user_scopes.scope_value', $territory->country)

            ->whereNull('users.suspended_at')

            ->exists()) {

            throw new \DomainException('Un responsable de pays existe deja pour ce pays.');

        }



        $user = DB::transaction(function () use ($application, $email, $isCountryLead, $territory) {

            $user = User::create([

                'name' => $application->full_name,

                'email' => $email,

                'password' => Str::password(32),

                'role' => 'coordinator',

                'territory_id' => $application->territory_id,

            ]);

            $user->forceFill([

                'access_source' => 'committee',

                'committee_application_id' => $application->id,

                'must_change_password' => true,

            ])->save();



            if ($isCountryLead) {

                DB::table('user_scopes')->insert([

                    'user_id' => $user->id,

                    'scope_type' => 'country',

                    'scope_id' => null,

                    'scope_value' => $territory->country,

                    'role' => 'coordinator',

                    'created_at' => now(),

                    'updated_at' => now(),

                ]);

            }



            return $user;

        });



        try {

            $token = Password::broker('invitations')->createToken($user);

            $url = route('invitation.show', ['token' => $token, 'email' => $user->email]);

            Mail::to($user->email)->send(new AccountCreated($user, $url));

            $this->mailSent = true;

        } catch (\Throwable $e) {

            report($e);

        }



        $details = [

            'Candidature' => (string) $application->id,

            'Grade' => (string) $application->grade,

            'Perimetre' => $isCountryLead ? 'pays '.$territory->country : 'ville '.$territory->city,

        ];

        $audit = app(SensitiveActionService::class);

        if ($request) {

            try {

                $audit->log($request, $actor, 'Compte comite cree', $details + ['Compte' => $user->email]);

            } catch (\Throwable $e) {

                report($e);

            }

        }

        $audit->notify($user, 'Compte comite cree', $details + ['Cree par' => (string) $actor->name], $request);



        return $user;

    }

}

