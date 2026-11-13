<?php



namespace App\Console\Commands;



use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class ChatbotSyncSiteFaq extends Command

{

    protected $signature = 'chatbot:sync-site-faq {--dry-run : Affiche sans ecrire}';



    protected $description = 'Importe la FAQ et les infos de confidentialite du site dans la base du chatbot';



    private const PRIVACY = [

        'ce que nous collectons' => "Quelles donn\u{e9}es collectez-vous ?",

        'pourquoi' => "Pourquoi collectez-vous mes donn\u{e9}es ?",

        'qui y a acces' => "Qui a acc\u{e8}s \u{e0} mes donn\u{e9}es ?",

        'vos droits' => "Quels sont mes droits sur mes donn\u{e9}es ?",

    ];



    public function handle(): int

    {

        $dry = (bool) $this->option('dry-run');

        $path = resource_path('views/home/about.blade.php');

        $src = is_file($path) ? file_get_contents($path) : false;

        if ($src === false) {

            $this->error('Fichier introuvable : '.$path);



            return self::FAILURE;

        }



        $clean = fn (string $t): string => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');

        $ascii = fn (string $t): string => Str::ascii(mb_strtolower($clean($t)));



        $items = [];

        if (preg_match('/<section id="faq".*?<\/section>/s', $src, $sec)) {

            preg_match_all('/<details[^>]*>\s*<summary>(.*?)<\/summary>\s*<p>(.*?)<\/p>\s*<\/details>/s', $sec[0], $m, PREG_SET_ORDER);

            foreach ($m as $x) {

                $items[] = ['FAQ', $x[1], $x[2]];

            }

        }

        preg_match_all('/<div>\s*<h3>(.*?)<\/h3>\s*<p>(.*?)<\/p>\s*<\/div>/s', $src, $p, PREG_SET_ORDER);

        foreach ($p as $x) {

            $key = $ascii($x[1]);

            if (isset(self::PRIVACY[$key])) {

                $items[] = ["Donn\u{e9}es", self::PRIVACY[$key], $x[2]];

            }

        }



        $new = 0;

        $upd = 0;

        $same = 0;

        $review = [];

        foreach ($items as [$cat, $q, $a]) {

            $question = $clean($q);

            $answer = $clean($a);

            if ($question === '' || $answer === '') {

                continue;

            }

            $needsReview = (bool) preg_match('/\{\{|\{!!|@[a-z]+\s*\(/', $answer);

            $row = DB::table('chatbot_faqs')->where('question', $question)->where('source', 'site')->first();

            if (! $row) {

                $new++;

                if ($needsReview) {

                    $review[] = $question;

                }

                if (! $dry) {

                    DB::table('chatbot_faqs')->insert([

                        'category' => $cat, 'question' => $question, 'answer' => $answer,

                        'active' => $needsReview ? 0 : 1, 'source' => 'site',

                        'created_at' => now(), 'updated_at' => now(),

                    ]);

                }

            } elseif ($row->answer !== $answer && ! $needsReview) {

                $upd++;

                if (! $dry) {

                    DB::table('chatbot_faqs')->where('id', $row->id)->update(['answer' => $answer, 'updated_at' => now()]);

                }

            } else {

                $same++;

            }

        }



        $this->line(($dry ? '[SIMULATION] ' : '')."Trouv\u{e9}s : ".count($items)." | nouveaux : $new | mis \u{e0} jour : $upd | inchang\u{e9}s : $same");

        foreach ($review as $r) {

            $this->warn('A relire (desactive) : '.mb_substr($r, 0, 90));

        }



        return self::SUCCESS;

    }

}

