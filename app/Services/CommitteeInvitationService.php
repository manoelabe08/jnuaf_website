<?php



namespace App\Services;



use App\Mail\TeamMessage;

use App\Models\CommitteeApplication;

use App\Models\User;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;



class CommitteeInvitationService

{

    public const MAX_BATCH = 20;



    public const MAX_PER_DAY = 40;



    /** @return array<int, string> */

    public function parse(string $raw): array

    {

        $list = [];

        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $part) {

            $e = mb_strtolower(trim($part));

            if ($e !== '') {

                $list[$e] = true;

            }

        }



        return array_keys($list);

    }



    /** @return array{todo: array<int, string>, skipped: array<string, string>} */

    public function plan(array $emails): array

    {

        $todo = [];

        $skipped = [];

        foreach ($emails as $e) {

            if (! filter_var($e, FILTER_VALIDATE_EMAIL)) {

                $skipped[$e] = 'adresse invalide';

            } elseif (DB::table('committee_invitations')->where('email', $e)->exists()) {

                $skipped[$e] = 'deja invitee';

            } elseif (CommitteeApplication::whereRaw('LOWER(email) = ?', [$e])->exists()) {

                $skipped[$e] = 'a deja candidate';

            } elseif (User::whereRaw('LOWER(email) = ?', [$e])->exists()) {

                $skipped[$e] = 'a deja un compte';

            } else {

                $todo[] = $e;

            }

        }



        return ['todo' => $todo, 'skipped' => $skipped];

    }



    public function link(): string

    {

        return (string) (config('jnuaf_access.invite_link') ?: rtrim((string) config('app.url'), '/').'/#candidature');

    }



    /** @return array{0: string, 1: string} */

    public function message(): array

    {

        $subject = 'JNUAF '.config('jnuaf.edition')." : rejoindre le comit\u{e9} d'organisation ?";

        $body = str_replace('{LINK}', $this->link(), "Bonjour,\n\nVous avez d\u{e9}j\u{e0} crois\u{e9} la route de la JNUAF (Journ\u{e9}e du Num\u{e9}rique au F\u{e9}minin), et nous vous en remercions. L'\u{e9}dition ".config('jnuaf.edition')." se pr\u{e9}pare et nous constituons les comit\u{e9}s d'organisation par ville et par pays.\n\nSi vous souhaitez toujours en faire partie, il suffit de remplir le formulaire de candidature :\n{LINK}\n\nPr\u{e9}voyez un CV et une lettre de motivation au format PDF, ainsi qu'une photo, qui servira \u{e0} votre carte de membre et \u{e0} la pr\u{e9}sentation de l'\u{e9}quipe sur le site.\n\nVotre candidature sera examin\u{e9}e par l'\u{e9}quipe et vous recevrez une r\u{e9}ponse par e-mail.\n\nSi vous ne souhaitez plus participer, vous n'avez rien \u{e0} faire : nous ne vous relancerons pas.\n\nMerci pour votre engagement.");



        return [$subject, $body];

    }



    public function send(array $emails, ?User $actor = null, ?Request $request = null): int

    {

        $todo = $this->plan($emails)['todo'];

        if (! $todo) {

            throw new \DomainException('Plus aucune adresse a inviter.');

        }

        if (count($todo) > self::MAX_BATCH) {

            throw new \DomainException('Pas plus de '.self::MAX_BATCH.' adresses a la fois.');

        }

        $recent = DB::table('committee_invitations')->where('sent_at', '>=', now()->subDay())->count();

        if ($recent + count($todo) > self::MAX_PER_DAY) {

            throw new \DomainException('Limite de '.self::MAX_PER_DAY.' invitations par 24 heures atteinte.');

        }



        [$subject, $body] = $this->message();

        $sent = 0;

        foreach ($todo as $e) {

            try {

                $id = DB::table('committee_invitations')->insertGetId([

                    'email' => $e,

                    'invited_by' => $actor?->id,

                    'sent_at' => now(),

                    'created_at' => now(),

                    'updated_at' => now(),

                ]);

            } catch (\Throwable $ex) {

                continue;

            }

            try {

                Mail::mailer('team')->to($e)->send(new TeamMessage($subject, $body));

                $sent++;

            } catch (\Throwable $ex) {

                report($ex);

                DB::table('committee_invitations')->where('id', $id)->delete();

            }

            usleep(300000);

        }



        if ($actor) {

            $audit = app(SensitiveActionService::class);

            $details = ['Envoyees' => (string) $sent, 'Prevues' => (string) count($todo)];

            if ($request) {

                try {

                    $audit->log($request, $actor, 'Invitations comite envoyees', $details);

                } catch (\Throwable $ex) {

                    report($ex);

                }

            }

            $audit->notify($actor, 'Invitations comite envoyees', $details, $request);

        }



        return $sent;

    }

}

