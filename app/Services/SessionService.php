<?php



namespace App\Services;



use App\Models\User;

use Illuminate\Support\Facades\DB;



class SessionService

{

    public function endAll(User $user): int

    {

        return $this->query($user)?->delete() ?? 0;

    }



    public function endOthers(User $user, ?string $keepSessionId): int

    {

        $query = $this->query($user);

        if (! $query) {

            return 0;

        }



        if ($keepSessionId) {

            $query->where('id', '!=', $keepSessionId);

        }



        return $query->delete();

    }



    private function query(User $user)

    {

        if (config('session.driver') !== 'database') {

            return null;

        }



        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->id);

    }

}

