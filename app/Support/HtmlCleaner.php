<?php



namespace App\Support;



class HtmlCleaner

{

    private const TAGS = ['p','br','h2','h3','h4','strong','b','em','i','u','s','ul','ol','li','blockquote','a','img','pre','code','span','div','hr','table','thead','tbody','tr','th','td','h1','h5','h6','sub','sup','small','figure','figcaption'];

    private const DROP = ['script','style','object','embed','form','input','button','textarea','select','link','meta','svg','math','iframe'];



    public static function clean(string $html): string

    {

        if (trim($html) === '') { return ''; }

        $dom = new \DOMDocument();

        libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        $root = $dom->getElementsByTagName('div')->item(0);

        if (!$root) { return ''; }

        self::walk($root);

        $out = '';

        foreach ($root->childNodes as $c) { $out .= $dom->saveHTML($c); }

        return $out;

    }



    private static function walk(\DOMNode $node): void

    {

        foreach (iterator_to_array($node->childNodes) as $child) {

            if ($child instanceof \DOMElement) {

                $tag = strtolower($child->nodeName);

                if (in_array($tag, self::DROP, true)) { $node->removeChild($child); continue; }

                if (!in_array($tag, self::TAGS, true)) {

                    self::walk($child);

                    while ($child->firstChild) { $node->insertBefore($child->firstChild, $child); }

                    $node->removeChild($child);

                    continue;

                }

                self::attrs($child, $tag);

                self::walk($child);

            } elseif ($child instanceof \DOMComment) {

                $node->removeChild($child);

            }

        }

    }




    private static function style(string $v): string

    {

        $out = [];

        foreach (explode(';', $v) as $decl) {

            $parts = explode(':', $decl, 2);

            if (count($parts) !== 2) { continue; }

            $p = strtolower(trim($parts[0]));

            $val = strtolower(trim($parts[1]));

            $ok = false;

            if ($p === 'text-align') { $ok = in_array($val, ['left','right','center','justify'], true); }

            elseif ($p === 'float') { $ok = in_array($val, ['left','right','none'], true); }

            elseif (in_array($p, ['width','height','max-width'], true)) { $ok = (bool) preg_match('/^\d{1,4}(\.\d+)?(px|%)$/', $val); }

            elseif (in_array($p, ['padding-left','margin-left','margin-right'], true)) { $ok = (bool) preg_match('/^(\d{1,3}(px|%)|auto)$/', $val); }

            if ($ok) { $out[] = $p . ':' . $val; }

        }

        return implode(';', $out);

    }




    private static function attrs(\DOMElement $el, string $tag): void

    {

        foreach (iterator_to_array($el->attributes) as $a) {

            $n = strtolower($a->name);

            $v = trim($a->value);

            $keep = false;

            if ($n === 'class') {

                $tokens = array_filter(preg_split('/\s+/', $v), function ($t) { return preg_match('/^ql-(align|indent)-[a-z0-9]+$/', $t); });

                if ($tokens) { $el->setAttribute('class', implode(' ', $tokens)); $keep = true; }

            } elseif ($n === 'style') {
                $st = self::style($v);
                if ($st !== '') { $el->setAttribute('style', $st); $keep = true; }
            } elseif ($tag === 'a' && $n === 'href') {

                $keep = (bool) preg_match('#^(https?://|mailto:|/|\#)#i', $v);

            } elseif ($tag === 'a' && $n === 'target') {

                $keep = ($v === '_blank');

            } elseif ($tag === 'img' && $n === 'src') {

                $keep = (bool) preg_match('#^(https?://|/)#i', $v);

            } elseif ($tag === 'img' && in_array($n, ['alt','width','height'], true)) {

                $keep = true;

            } elseif (in_array($tag, ['td','th'], true) && in_array($n, ['colspan','rowspan'], true)) {

                $keep = true;

            }

            if (!$keep) { $el->removeAttribute($a->name); }

        }

        if ($tag === 'a' && $el->getAttribute('target') === '_blank') { $el->setAttribute('rel', 'noopener noreferrer'); }

    }

}

