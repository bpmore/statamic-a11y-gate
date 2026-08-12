<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

use DOMElement;
use Normalizer;

/**
 * The accessible name of a link or button, and its normalised form.
 *
 * axe-core only flags a control with NO accessible name. Judging whether the
 * words describe the destination is this checker's own rule, so the name is
 * computed here and compared in a normalised form rather than raw: "Click
 * here!", "Click Here »" and "click here →" all reduce to "click here".
 */
final class AccessibleName
{
    /**
     * The control's accessible name, in the rough order the platform resolves it:
     * aria-labelledby, then aria-label, then the text content (or the alt text of
     * an image inside it when there is no text), then title.
     */
    public static function for(DOMElement $el): string
    {
        $doc = $el->ownerDocument;

        if ($el->hasAttribute('aria-labelledby') && $doc !== null) {
            $parts = [];
            foreach (preg_split('/\s+/', trim($el->getAttribute('aria-labelledby'))) ?: [] as $id) {
                if ($id !== '' && ($ref = $doc->getElementById($id)) !== null) {
                    $parts[] = $ref->textContent;
                }
            }
            $name = trim(implode(' ', $parts));
            if ($name !== '') {
                return $name;
            }
        }

        $ariaLabel = trim($el->getAttribute('aria-label'));
        if ($ariaLabel !== '') {
            return $ariaLabel;
        }

        $text = trim($el->textContent);
        if ($text === '') {
            foreach ($el->getElementsByTagName('img') as $img) {
                $alt = trim($img->getAttribute('alt'));
                if ($alt !== '') {
                    return $alt;
                }
            }
        }

        return $text !== '' ? $text : trim($el->getAttribute('title'));
    }

    /**
     * Normalise an accessible name for comparison: Unicode-fold, lowercase, strip
     * punctuation, emoji, arrows and other symbols (anything that is not a letter,
     * number or space), collapse whitespace, and trim.
     *
     * The Unicode fold needs ext-intl and is skipped without it, which is a real
     * behaviour difference rather than a tidy fallback: without the extension,
     * "Ｃｌｉｃｋ ｈｅｒｅ" in full-width characters is not folded to "click here"
     * and the link passes. ext-intl is suggested in composer.json for that reason.
     */
    public static function normalize(string $name): string
    {
        if (class_exists(Normalizer::class)) {
            $name = Normalizer::normalize($name, Normalizer::FORM_KC) ?: $name;
        }

        $name = mb_strtolower($name);
        $name = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $name) ?? $name;
        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;

        return trim($name);
    }
}
