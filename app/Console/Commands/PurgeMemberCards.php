<?php



namespace App\Console\Commands;



use App\Models\MemberCard;

use App\Services\MemberCardService;

use Illuminate\Console\Command;

use Illuminate\Support\Str;



class PurgeMemberCards extends Command

{

    protected $signature = 'cards:purge {--dry-run : Affiche ce qui serait anonymisé sans rien modifier}';



    protected $description = 'Anonymise les cartes de membre expirées ou révoquées depuis plus longtemps que la durée de conservation';



    public function handle(): int

    {

        $months = max(1, (int) config('jnuaf_cards.retention_months'));

        $limit = now()->subMonths($months);



        $query = MemberCard::query()

            ->whereNull('anonymized_at')

            ->where(function ($where) use ($limit) {

                $where->whereDate('valid_until', '<', $limit->toDateString())

                    ->orWhere('revoked_at', '<', $limit);

            });



        $count = (clone $query)->count();

        $dry = (bool) $this->option('dry-run');



        $this->info(sprintf(

            '%s%d carte(s) à anonymiser (conservation : %d mois).',

            $dry ? '[simulation] ' : '',

            $count,

            $months

        ));



        if ($dry || $count === 0) {

            return self::SUCCESS;

        }



        $query->chunkById(100, function ($chunk) {

            foreach ($chunk as $card) {

                $this->anonymize($card);

            }

        });



        $this->info('Anonymisation terminée.');



        return self::SUCCESS;

    }



    private function anonymize(MemberCard $card): void

    {

        if ($card->photo_path && is_file($card->photo_path)) {

            @unlink($card->photo_path);

        }



        $card->forceFill([

            'full_name' => 'Anonyme',

            'email' => null,

            'photo_path' => null,

            'role_label' => MemberCardService::CATEGORIES[$card->category] ?? 'Membre',

            'verify_token' => Str::random(40),

            'anonymized_at' => now(),

        ])->save();

    }

}

