<?php



namespace App\Console\Commands;



use App\Models\NewsletterDelivery;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Log;



class ForceNewsletterSend extends Command

{

    protected $signature = 'newsletter:force-send {count=500 : Nombre de messages} {--oui : Confirme le forcage} {--cancel : Annule un forcage en cours}';



    protected $description = 'Autorise immediatement les N prochains envois en attente, hors plafonds internes';



    public function handle(): int

    {

        if ($this->option('cancel')) {

            Cache::forget('jnuaf-newsletter-force');

            Cache::forget('jnuaf-newsletter-cooldown');

            Log::info('newsletter:force-send annule');

            $this->info('Forcage annule, reprise du rythme normal.');



            return self::SUCCESS;

        }



        $count = max(1, min(1000, (int) $this->argument('count')));

        $pending = NewsletterDelivery::where('status', 'pending')

            ->whereHas('campaign', fn ($q) => $q->whereIn('status', ['queued', 'sending']))

            ->count();

        $todo = min($count, $pending);

        $this->line('Messages en attente : '.$pending.' | forcage demande : '.$todo);



        if (! $this->option('oui')) {

            $this->warn('Simulation. Ajoutez --oui pour lancer le forcage.');



            return self::SUCCESS;

        }



        Cache::forget('jnuaf-newsletter-cooldown');

        Cache::put('jnuaf-newsletter-force', $todo, now()->addHours(6));

        Log::info('newsletter:force-send lance', ['count' => $todo]);

        $this->info('Forcage actif : '.$todo.' messages partiront avec la tache normale des prochaines minutes.');



        return self::SUCCESS;

    }

}

