<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\AccessibleName;
use Bpmore\A11yGate\Accessibility\LinkTextVocabulary;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMElement;
use DOMXPath;

/**
 * Link text that does not say where it goes.
 *
 * WCAG 2.4.4, plus house rules. The awkward family: the vague-phrase judgement
 * is a word list rather than a property of the markup, which makes it the rule
 * most likely to drift between the two forks. The list lives in
 * `LinkTextVocabulary` and its defaults are Windrow's current values.
 *
 * Covers empty accessible names on both links and buttons, wholly generic text
 * ("click here"), vague text, and links with nowhere to go.
 *
 * `link-goes-nowhere` is a house rule and stays a warning. An in-page anchor is
 * not it: "#main" is the skip link that makes a page navigable, so the check
 * turns on the fragment being empty rather than on the "#".
 */
final class LinkPurposeCheck extends RuleCheck
{
    public function __construct(
        private readonly LinkTextVocabulary $vocabulary = new LinkTextVocabulary,
    ) {}

    public static function key(): string
    {
        return 'a11y.link.purpose';
    }

    public static function rules(): array
    {
        return ['button-empty', 'link-empty', 'link-goes-nowhere', 'link-unclear', 'link-vague'];
    }

    /**
     * Compares the *normalised* accessible name of every link and button against
     * the vocabulary, so "Click here!" and "click here →" are caught like "click
     * here", and the name is read from aria labels and image alt too. A
     * whole-name banned phrase or a banned substring refuses the publish
     * (link-unclear, error); a vague phrase in text that still names no
     * destination warns.
     *
     * Buttons are included because "click here" is no better on a button.
     *
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $generic = $this->vocabulary->genericWords();

        $violations = [];

        foreach ($xpath->query('//a | //button') as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }

            if ($el->nodeName === 'a' && $this->goesNowhere($el)) {
                $violations[] = $this->issue('link-goes-nowhere', Violation::WARN, $this->snippet(AccessibleName::for($el)));
            }

            $raw = AccessibleName::for($el);

            // An empty accessible name fails for both: a link with no text gives
            // no destination (2.4.4), a button with no name gives no action (4.1.2).
            if ($raw === '') {
                $violations[] = $this->nameless($el);

                continue;
            }

            if ($this->looksLikeUrl($raw)) {
                $violations[] = $this->issue('link-unclear', Violation::ERROR, $this->snippet($raw));

                continue;
            }

            $name = AccessibleName::normalize($raw);

            // A name that is all punctuation or symbols ("×", "→") normalises to
            // nothing, which leaves it as nameless as an empty control.
            if ($name === '') {
                $violations[] = $this->nameless($el);

                continue;
            }

            // Refuse: the whole name is a banned phrase, or it contains a banned
            // substring ("click here to read the report" is still "click here").
            if (in_array($name, $this->vocabulary->banned, true) || $this->containsAny($name, $this->vocabulary->bannedSubstrings)) {
                $violations[] = $this->issue('link-unclear', Violation::ERROR, $this->snippet($raw));

                continue;
            }

            // Warn: a vague phrase sits inside longer text that still names no
            // destination, because every word of it is generic or filler ("Learn
            // more about us"). Text that names its target ("Learn more about
            // Soundgarden") satisfies 2.4.4 and passes clean.
            if ($this->containsWords($name, $this->vocabulary->vague) && $this->allWordsGeneric($name, $generic)) {
                $violations[] = $this->issue('link-vague', Violation::WARN, $this->snippet($raw));
            }
        }

        return $violations;
    }

    private function nameless(DOMElement $el): Violation
    {
        return $el->nodeName === 'a'
            ? $this->issue('link-empty', Violation::ERROR, $el->getAttribute('href'))
            : $this->issue('button-empty', Violation::ERROR, $el->getAttribute('id'));
    }

    /**
     * @param  array<string, true>  $generic
     */
    private function allWordsGeneric(string $name, array $generic): bool
    {
        foreach (explode(' ', $name) as $word) {
            if ($word !== '' && ! isset($generic[$word])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function containsAny(string $name, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($name, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether a phrase appears as whole words inside longer text, so a vague
     * phrase warns when it is buried in a sentence while the whole-name case is
     * handled above as a refusal.
     *
     * @param  array<int, string>  $phrases
     */
    private function containsWords(string $name, array $phrases): bool
    {
        $padded = ' '.$name.' ';
        foreach ($phrases as $phrase) {
            if ($phrase !== '' && $name !== $phrase && str_contains($padded, ' '.$phrase.' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * A link with nowhere to go: href="#", an empty href, or a javascript: URL
     * that does nothing. Activating any of them returns the reader precisely
     * where they were, having announced itself as a link first.
     *
     * An in-page anchor is NOT this. "#main" is the skip link, the thing that
     * makes a page navigable, and every heading anchor is the same shape, so the
     * check turns on the fragment being empty rather than on the "#".
     *
     * An <a> with no href at all is not a link and is not focusable, and the
     * empty-name rule above already covers that shape.
     */
    private function goesNowhere(DOMElement $el): bool
    {
        if (! $el->hasAttribute('href')) {
            return false;
        }

        $href = trim($el->getAttribute('href'));

        if ($href === '' || $href === '#') {
            return true;
        }

        return str_starts_with(strtolower($href), 'javascript:');
    }
}
