<?php



namespace App\Services\Chatbot;



use App\Models\Event;

use App\Models\Territory;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;



class ChatbotEngine

{

    private const STOP = ['le', 'la', 'les', 'un', 'une', 'des', 'de', 'du', 'et', 'en', 'au', 'aux', 'je', 'tu', 'il', 'elle', 'nous', 'vous', 'ils', 'est', 'sont', 'pour', 'par', 'sur', 'dans', 'que', 'qui', 'quoi', 'quel', 'quelle', 'quels', 'quelles', 'ce', 'cet', 'cette', 'ces', 'mon', 'ma', 'mes', 'ton', 'ta', 'tes', 'son', 'sa', 'ses', 'ne', 'pas', 'se', 'si', 'ou', 'comment', 'quand', 'peut', 'peux', 'veux', 'voudrais', 'faire', 'avoir', 'etre', 'avec', 'sans', 'plus', 'tres', 'bonjour', 'salut', 'svp'];



    public function normalize(string $text): string

    {

        $t = Str::ascii(mb_strtolower($text));

        $t = preg_replace('/[^a-z0-9]+/', ' ', $t) ?? '';



        return trim(preg_replace('/\s+/', ' ', $t) ?? '');

    }



    public function tokens(string $text): array

    {

        $out = [];

        foreach (explode(' ', $this->normalize($text)) as $w) {

            if ($w === '' || in_array($w, self::STOP, true)) {

                continue;

            }

            if (strlen($w) > 3 && (substr($w, -1) === 's' || substr($w, -1) === 'x')) {

                $w = substr($w, 0, -1);

            }

            if (strlen($w) >= 6) { $w = substr($w, 0, 5); } // jnuaf-s49
            if (strlen($w) >= 2) {

                $out[$w] = true;

            }

        }



        return array_keys($out);

    }



    private function index(): array

    {

        $rows = DB::table('chatbot_faqs')->where('active', 1)

            ->get(['id', 'category', 'question', 'answer', 'keywords', 'variants', 'hits', 'helpful_yes', 'helpful_no']);

        $docs = [];

        foreach ($rows as $r) {

            $docs[] = [

                'row' => $r,

                'q' => $this->tokens((string) $r->question),

                'k' => $this->tokens((string) $r->keywords),

                'v' => $this->tokens((string) $r->variants),

                'a' => $this->tokens((string) $r->answer),

            ];

        }



        return $docs;

    }



    private function score(array $qt, array $doc, bool $prefixLast): float

    {

        if ($qt === []) {

            return 0.0;

        }

        $total = 0.0;

        $last = count($qt) - 1;

        foreach ($qt as $i => $tok) {

            $best = 0.0;

            foreach ([['q', 2.0], ['k', 2.0], ['v', 1.5], ['a', 0.5]] as [$field, $w]) {

                foreach ($doc[$field] as $d) {

                    if ($d === $tok || ($prefixLast && $i === $last && strlen($tok) >= 2 && str_starts_with($d, $tok))) {

                        $best = max($best, $w);

                        break;

                    }

                }

            }

            $total += $best;

        }



        return $total / (2.0 * count($qt));

    }



    public function suggest(string $query, int $limit = 5): array

    {

        $qt = $this->tokens($query);

        if ($qt === []) {

            return [];

        }

        $scored = [];

        foreach ($this->index() as $doc) {

            $s = $this->score($qt, $doc, true);

            if ($s >= 0.25) {

                $r = $doc['row'];

                $pop = max(0.0, min(0.1, ($r->hits + $r->helpful_yes - $r->helpful_no) / 1000));

                $scored[] = ['id' => (int) $r->id, 'question' => (string) $r->question, 'score' => $s + $pop];

            }

        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);



        return array_map(fn ($x) => ['id' => $x['id'], 'question' => $x['question']], array_slice($scored, 0, $limit));

    }



    public function answer(string $query): array

    {

        $event = $this->eventAnswer($query);

        if ($event !== null) {

            return ['type' => 'event', 'text' => $event];

        }



        $qt = $this->tokens($query);

        $best = null;

        $bestScore = 0.0;

        foreach ($this->index() as $doc) {

            $s = $this->score($qt, $doc, false);

            if ($s > $bestScore) {

                $bestScore = $s;

                $best = $doc['row'];

            }

        }

        if ($best !== null && $bestScore >= 0.6) {

            return ['type' => 'faq', 'faq_id' => (int) $best->id, 'question' => (string) $best->question, 'text' => (string) $best->answer, 'score' => round($bestScore, 2)];

        }



        $doc = $this->passageAnswer($query);

        if ($doc !== null) {

            return $doc;

        }



        return ['type' => 'none', 'suggestions' => $this->suggest($query, 3)];

    }




    public function passageAnswer(string $query): ?array

    {

        $qt = $this->tokens($query);

        if ($qt === []) {

            return null;

        }

        $rows = DB::table('chatbot_passages as p')

            ->join('chatbot_sources as s', 's.id', '=', 'p.source_id')

            ->where('s.active', 1)

            ->get(['p.body', 'p.tokens', 'p.page', 's.title', 's.url']);

        $best = null;

        $bestScore = 0.0;

        $bestHits = 0;

        foreach ($rows as $r) {

            $set = array_flip(explode(' ', (string) $r->tokens));

            $hits = 0;

            foreach ($qt as $tok) {

                if (isset($set[$tok])) {

                    $hits++;

                }

            }

            $score = $hits / count($qt);

            if ($hits >= min(2, count($qt)) && $score >= 0.6 && ($score > $bestScore || ($score == $bestScore && $hits > $bestHits))) {

                $best = $r;

                $bestScore = $score;

                $bestHits = $hits;

            }

        }

        if ($best === null) {

            return null;

        }

        $text = mb_strimwidth((string) $best->body, 0, 600, '...') . "\n\nSource : " . $best->title . ($best->page ? ' (p. ' . $best->page . ')' : '') . ($best->url ? "\n" . $best->url : '');



        return ['type' => 'doc', 'text' => $text, 'score' => round($bestScore, 2)];

    }



    private function eventAnswer(string $query): ?string

    {

        // jnuaf-s48

        $n = ' '.$this->normalize($query).' ';

        $intent = false;

        foreach (['quand', 'date', 'dates', 'jour', 'lieu', 'adresse', 'horaire', 'horaires', 'heure', 'edition', 'evenement', 'prochaine'] as $w) {

            if (str_contains($n, ' '.$w.' ')) {

                $intent = true;

                break;

            }

        }

        if (! $intent) {

            return null;

        }



        $generic = ['jnuaf', 'journee', 'numerique', 'feminin', 'edition', 'evenement', 'campus', 'universite', 'ecole', 'saint', 'sainte', 'ville'];

        $words = fn (string $label): array => array_values(array_filter(

            explode(' ', $this->normalize($label)),

            fn ($w) => strlen($w) >= 4 && ! ctype_digit($w) && ! in_array($w, $generic, true)

        ));

        $asked = fn (array $ws): bool => count(array_filter($ws, fn ($w) => str_contains($n, ' '.$w))) > 0;



        $events = Event::query()->where('is_published', true)->where('starts_at', '>=', now()->subDay())->with('territory')->orderBy('starts_at')->get();



        $matched = $events->filter(fn ($e) => $asked($words(($e->territory?->name ?? '').' '.$e->title)));

        if ($matched->isNotEmpty()) {

            return $matched->take(3)->map(fn ($e) => $this->describeEvent($e))->implode("\n");

        }



        foreach (Territory::active()->get() as $t) {

            if ($asked($words((string) $t->name))) {

                return "Aucune \u{e9}dition n'est encore programm\u{e9}e pour ".$t->name.' pour le moment.';

            }

        }



        if ($events->isEmpty()) {

            return null;

        }



        return "Voici les prochaines \u{e9}ditions :\n".$events->take(5)->map(fn ($e) => '- '.$this->describeEvent($e, true))->implode("\n");

    }




    private function describeEvent(Event $e, bool $short = false): string

    {

        $text = $this->describeEventBase($e, $short);

        if ($e->slug) {

            $url = url('evenements/' . $e->slug);

            if ($e->registration_open) {

                $text .= $short ? ' - Inscription : ' . $url : ' Inscrivez-vous ici : ' . $url;

            } elseif (! $short) {

                $text .= " Plus d'informations : " . $url;

            }

        }



        return $text;

    }



    private function describeEventBase(Event $e, bool $short = false): string

    {

        $d = $e->starts_at;

        $when = $d->copy()->locale('fr')->translatedFormat('l j F Y')." \u{e0} ".$d->format('H\hi');

        $text = $e->title.' : '.$when;

        if ($e->location) {

            $text .= ' - '.$e->location;

        }

        if (! $short) {

            $text .= $e->registration_open ? '. Les inscriptions sont ouvertes.' : ". Les inscriptions ne sont pas ouvertes pour le moment.";

        }



        return $text;

    }



    public function recordUnanswered(string $query): void

    {

        $tokens = $this->tokens($query);

        if ($tokens === []) {

            return;

        }

        sort($tokens);

        $norm = mb_substr(implode(' ', $tokens), 0, 250);

        $now = now();

        $row = DB::table('chatbot_unanswered')->where('normalized', $norm)->first();

        if ($row) {

            DB::table('chatbot_unanswered')->where('id', $row->id)->update(['occurrences' => $row->occurrences + 1, 'last_seen_at' => $now, 'updated_at' => $now]);

        } else {

            DB::table('chatbot_unanswered')->insert(['normalized' => $norm, 'sample' => mb_substr($query, 0, 500), 'occurrences' => 1, 'status' => 'open', 'last_seen_at' => $now, 'created_at' => $now, 'updated_at' => $now]);

        }

    }

}

