<?php



namespace App\Support;



use DOMDocument;

use DOMElement;

use DOMNode;



class NewsletterHtml

{

    private const TAGS = ['p','br','strong','b','em','i','u','a','ul','ol','li','h1','h2','h3','h4','table','thead','tbody','tr','td','th','div','span','img','hr','small','center','blockquote'];

    private const DROP = ['script','style','iframe','object','embed','form','input','button','textarea','select','link','meta','base','svg','math','head','title','noscript','audio','video','source'];

    private const ATTRS = ['style','href','target','rel','src','alt','title','width','height','align','valign','bgcolor','cellpadding','cellspacing','border','role','colspan','rowspan'];



    public static function isHtml(string $body): bool

    {

        return (bool) preg_match('/<(p|div|table|a|h[1-6]|ul|ol|br|img|span|strong)\b/i', $body);

    }



    public static function render(string $body): string

    {

        return self::isHtml($body) ? self::sanitize($body) : nl2br(e($body));

    }



    public static function sanitize(string $html): string

    {

        $dom = new DOMDocument('1.0', 'UTF-8');

        $prev = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8"><div id="nl-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        libxml_clear_errors();

        libxml_use_internal_errors($prev);

        $root = $dom->getElementsByTagName('div')->item(0);

        if (! $root) {

            return e($html);

        }

        self::clean($root);

        $out = '';

        foreach ($root->childNodes as $child) {

            $out .= $dom->saveHTML($child);

        }

        return $out;

    }



    private static function clean(DOMNode $node): void

    {

        foreach (iterator_to_array($node->childNodes) as $child) {

            if ($child instanceof DOMElement) {

                $tag = strtolower($child->nodeName);

                if (in_array($tag, self::DROP, true)) {

                    $node->removeChild($child);

                    continue;

                }

                self::clean($child);

                if (! in_array($tag, self::TAGS, true)) {

                    while ($child->firstChild) {

                        $node->insertBefore($child->firstChild, $child);

                    }

                    $node->removeChild($child);

                    continue;

                }

                self::cleanAttributes($child, $tag);

            } elseif ($child->nodeType !== XML_TEXT_NODE) {

                $node->removeChild($child);

            }

        }

    }



    private static function cleanAttributes(DOMElement $el, string $tag): void

    {

        foreach (iterator_to_array($el->attributes) as $attr) {

            $name = strtolower($attr->nodeName);

            $value = trim((string) $attr->nodeValue);

            if (! in_array($name, self::ATTRS, true)) {

                $el->removeAttribute($attr->nodeName);

            } elseif ($name === 'href' && ! preg_match('#^(https?://|mailto:|tel:)#i', $value)) {

                $el->removeAttribute($attr->nodeName);

            } elseif ($name === 'src' && ! preg_match('#^https://#i', $value)) {

                $el->removeAttribute($attr->nodeName);

            } elseif ($name === 'style') {

                $clean = self::cleanStyle($value);

                if ($clean === '') {

                    $el->removeAttribute('style');

                } else {

                    $el->setAttribute('style', $clean);

                }

            }

        }

        if ($tag === 'a') {

            $el->setAttribute('rel', 'noopener noreferrer');

            if ($el->hasAttribute('href')) {

                $el->setAttribute('target', '_blank');

            }

        }

        if ($tag === 'img' && ! $el->hasAttribute('src') && $el->parentNode) {

            $el->parentNode->removeChild($el);

        }

    }



    private static function cleanStyle(string $style): string

    {

        $keep = [];

        foreach (explode(';', $style) as $decl) {

            $decl = trim($decl);

            if ($decl === '' || ! str_contains($decl, ':')) {

                continue;

            }

            if (preg_match('/(url\s*\(|expression|javascript|behavior|binding|@import|\\\\|<|>)/i', $decl)) {

                continue;

            }

            [$prop] = explode(':', $decl, 2);

            if (in_array(strtolower(trim($prop)), ['position', 'z-index', 'top', 'left', 'right', 'bottom'], true)) {

                continue;

            }

            $keep[] = $decl;

        }

        return implode(';', $keep);

    }



    public static function toText(string $body): string

    {

        if (! self::isHtml($body)) {

            return $body;

        }

        $t = preg_replace('#<div[^>]*display\s*:\s*none[^>]*>.*?</div>#is', '', $body);

        $t = preg_replace_callback('#<a\s[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is', function ($m) {

            $label = trim(strip_tags($m[2]));

            $url = html_entity_decode($m[1]);

            return ($label === '' || $label === $url) ? $url : $label.' ('.$url.')';

        }, $t);

        $t = preg_replace(['#<br\s*/?>#i', '#<li[^>]*>#i', '#</(p|div|tr|h[1-6]|table|ul|ol|li)>#i', '#</t[dh]>#i'], ["\n", '- ', "\n", ' '], $t);

        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $t = preg_replace('/[ \t]+/', ' ', $t);

        $t = preg_replace('/ *\n */', "\n", $t);

        return trim(preg_replace("/\n{3,}/", "\n\n", $t));

    }

}

