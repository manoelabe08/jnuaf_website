<?php



namespace App\Services\Chatbot;



use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Http;



class KnowledgeIngest

{

    public function chunk(string $text, int $max = 800): array

    {

        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;

        $text = preg_replace('/\n{2,}/', "\n\n", $text) ?? $text;

        $paras = [];

        foreach (explode("\n\n", $text) as $p) {

            $p = trim(preg_replace('/\s*\n\s*/', ' ', $p) ?? $p);

            if ($p !== '') {

                $paras[] = $p;

            }

        }

        $units = [];

        foreach ($paras as $p) {

            if (mb_strlen($p) <= $max) {

                $units[] = $p;

                continue;

            }

            $sentences = preg_split('/(?<=[.!?])\s+/u', $p) ?: [$p];

            foreach ($sentences as $s) {

                while (mb_strlen($s) > $max) {

                    $units[] = mb_substr($s, 0, $max);

                    $s = mb_substr($s, $max);

                }

                if (trim($s) !== '') {

                    $units[] = trim($s);

                }

            }

        }

        $out = [];

        $buf = '';

        foreach ($units as $u) {

            if ($buf !== '' && mb_strlen($buf) + 1 + mb_strlen($u) > $max) {

                $out[] = $buf;

                $buf = '';

            }

            $buf = $buf === '' ? $u : $buf . ' ' . $u;

        }

        if ($buf !== '') {

            $out[] = $buf;

        }



        return array_values(array_filter($out, fn ($x) => mb_strlen($x) >= 40));

    }



    public function replacePassages(int $sourceId, array $items): int

    {

        $engine = app(ChatbotEngine::class);

        DB::table('chatbot_passages')->where('source_id', $sourceId)->delete();

        $rows = [];

        $pos = 0;

        $chars = 0;

        foreach ($items as [$page, $text]) {

            foreach ($this->chunk((string) $text) as $c) {

                $tok = implode(' ', $engine->tokens($c));

                if ($tok === '') {

                    continue;

                }

                $rows[] = ['source_id' => $sourceId, 'position' => $pos++, 'page' => $page, 'body' => $c, 'tokens' => $tok, 'created_at' => now()];

                $chars += mb_strlen($c);

            }

        }

        foreach (array_chunk($rows, 200) as $part) {

            DB::table('chatbot_passages')->insert($part);

        }

        DB::table('chatbot_sources')->where('id', $sourceId)->update([

            'passage_count' => count($rows),

            'char_count' => $chars,

            'fetched_at' => now(),

            'error' => null,

            'updated_at' => now(),

        ]);



        return count($rows);

    }



    public function pdfPages(string $absPath): array

    {

        @ini_set('memory_limit', '512M');

        @set_time_limit(120);

        $parser = new \Smalot\PdfParser\Parser();

        $pdf = $parser->parseFile($absPath);

        $items = [];

        foreach ($pdf->getPages() as $i => $page) {

            $t = trim((string) $page->getText());

            if ($t !== '') {

                $items[] = [$i + 1, $t];

            }

        }



        return $items;

    }



    public function fetch(string $url): string

    {

        $parts = parse_url($url);

        if (! $parts || ! in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {

            throw new \RuntimeException('Adresse invalide.');

        }

        if (isset($parts['port']) && ! in_array((int) $parts['port'], [80, 443], true)) {

            throw new \RuntimeException('Port non autorise.');

        }

        $ip = gethostbyname($parts['host']);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {

            throw new \RuntimeException('Adresse non autorisee (reseau interne ou inconnue).');

        }

        $res = Http::timeout(15)

            ->withHeaders(['User-Agent' => 'JNUAF-Chatbot/1.0'])

            ->withOptions(['allow_redirects' => false])

            ->get($url);

        if ($res->redirect()) {

            throw new \RuntimeException("Redirection non suivie : indiquez l'adresse finale.");

        }

        if (! $res->successful()) {

            throw new \RuntimeException('Reponse HTTP ' . $res->status() . '.');

        }

        $ct = strtolower((string) $res->header('Content-Type'));

        if (strpos($ct, 'text/html') === false && strpos($ct, 'text/plain') === false) {

            throw new \RuntimeException('Type de contenu non pris en charge (HTML attendu).');

        }

        $body = $res->body();

        if (strlen($body) > 2000000) {

            throw new \RuntimeException('Page trop volumineuse.');

        }

        if (! mb_check_encoding($body, 'UTF-8')) {

            $body = mb_convert_encoding($body, 'UTF-8', 'ISO-8859-1');

        }



        return $body;

    }



    public function htmlText(string $html): array

    {

        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();

        $xp = new \DOMXPath($dom);

        foreach ($xp->query('//script|//style|//nav|//header|//footer|//aside|//form|//noscript|//svg|//iframe|//button') as $n) {

            $n->parentNode?->removeChild($n);

        }

        $title = trim((string) ($xp->query('//title')->item(0)?->textContent ?? ''));

        if ($title === '') {

            $title = trim((string) ($xp->query('//h1')->item(0)?->textContent ?? ''));

        }

        $root = $xp->query('//main')->item(0) ?? $xp->query('//article')->item(0) ?? $xp->query('//body')->item(0);

        if (! $root) {

            return [$title, ''];

        }

        foreach ($xp->query('.//p|.//li|.//h1|.//h2|.//h3|.//h4|.//tr|.//div', $root) as $n) {

            $n->appendChild($dom->createTextNode("\n\n"));

        }



        return [$title, (string) $root->textContent];

    }



    public function postText(string $html): string

    {

        $html = str_ireplace(['</p>', '<br>', '<br/>', '<br />', '</li>', '</h2>', '</h3>', '</h4>'], "\n\n", $html);



        return html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

    }

}

