<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class EmailBlastSanitizer
{
    private const DANGEROUS_TAGS = ['script', 'iframe', 'object', 'embed'];
    private const ALLOWED_TAGS = [
        'p',
        'br',
        'strong',
        'b',
        'em',
        'i',
        'u',
        'ul',
        'ol',
        'li',
        'blockquote',
        'h1',
        'h2',
        'h3',
        'h4',
        'a',
    ];

    public static function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->loadHTML(
                '<?xml encoding="UTF-8"><div>'.$html.'</div>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $wrapper = $document->documentElement;

            if (! $wrapper instanceof DOMElement) {
                return '';
            }

            self::sanitizeNode($wrapper);

            $output = '';

            foreach ($wrapper->childNodes as $childNode) {
                $output .= $document->saveHTML($childNode);
            }

            return trim($output);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    private static function sanitizeNode(DOMNode $node): void
    {
        for ($index = $node->childNodes->length - 1; $index >= 0; $index--) {
            $child = $node->childNodes->item($index);

            if (! $child instanceof DOMNode) {
                continue;
            }

            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->tagName);

                if (in_array($tagName, self::DANGEROUS_TAGS, true)) {
                    $child->parentNode?->removeChild($child);

                    continue;
                }

                if (! in_array($tagName, self::ALLOWED_TAGS, true)) {
                    self::unwrapNode($child);

                    continue;
                }

                self::sanitizeAttributes($child);
            }

            self::sanitizeNode($child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element): void
    {
        for ($index = $element->attributes->length - 1; $index >= 0; $index--) {
            $attribute = $element->attributes->item($index);

            if ($attribute === null) {
                continue;
            }

            $attributeName = strtolower($attribute->nodeName);

            if (str_starts_with($attributeName, 'on')) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            if (strtolower($element->tagName) !== 'a' || $attributeName !== 'href') {
                $element->removeAttributeNode($attribute);

                continue;
            }

            $sanitizedHref = self::sanitizeHref($attribute->nodeValue);

            if ($sanitizedHref === null) {
                $element->removeAttributeNode($attribute);

                continue;
            }

            $element->setAttribute('href', $sanitizedHref);
        }
    }

    private static function sanitizeHref(string $href): ?string
    {
        $href = trim($href);

        if ($href === '') {
            return null;
        }

        if (preg_match('/^\s*javascript:/i', $href) === 1) {
            return null;
        }

        if (
            preg_match('/^(https?:|mailto:|tel:|\/|#)/i', $href) === 1
            || ! preg_match('/^[a-z][a-z0-9+\-.]*:/i', $href)
        ) {
            return $href;
        }

        return null;
    }

    private static function unwrapNode(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent instanceof DOMNode) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
