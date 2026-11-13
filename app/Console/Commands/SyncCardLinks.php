<?php



namespace App\Console\Commands;



use App\Models\CommitteeApplication;

use App\Models\MemberCard;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Log;



class SyncCardLinks extends Command

{

    protected $signature = 'cards:sync-links {--dry-run}';



    protected $description = 'Aligne les cartes du comite sur l existence de leur candidature.';



    public function handle(): int

    {

        $dry = (bool) $this->option('dry-run');

        $alive = array_flip(CommitteeApplication::query()->pluck('id')->all());

        $revoked = 0;

        $restored = 0;



        $cards = MemberCard::query()

            ->where('source_type', 'committee')

            ->whereNull('anonymized_at')

            ->get();



        foreach ($cards as $card) {

            $exists = isset($alive[(int) $card->source_id]);



            if (! $exists && $card->revoked_at === null) {

                $this->line(($dry ? 'Revoquerait : ' : 'Revoquee : ').$card->number.' (candidature '.$card->source_id.')');

                if (! $dry) {

                    $card->forceFill(['revoked_at' => now(), 'revoked_reason' => 'source_deleted'])->save();

                }

                $revoked++;

            } elseif ($exists && $card->revoked_at !== null && $card->revoked_reason === 'source_deleted') {

                $this->line(($dry ? 'Reactiverait : ' : 'Reactivee : ').$card->number.' (candidature '.$card->source_id.')');

                if (! $dry) {

                    $card->forceFill(['revoked_at' => null, 'revoked_reason' => null])->save();

                }

                $restored++;

            }

        }



        if (! $dry && ($revoked || $restored)) {

            Log::info('cards:sync-links', ['revoquees' => $revoked, 'reactivees' => $restored]);

        }



        $this->info('Revoquees : '.$revoked.' | reactivees : '.$restored);



        return self::SUCCESS;

    }

}

