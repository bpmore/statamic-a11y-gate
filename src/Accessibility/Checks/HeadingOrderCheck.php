<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMXPath;

/**
 * Heading structure: a page with no h1, more than one, or a skipped level.
 *
 * House rules with plain names, deliberately NOT cited as WCAG 1.3.1. No
 * criterion covers any of these three, and axe tags the two it has as
 * best-practice with no WCAG tag at all. The full reasoning sits with the copy,
 * in `Remediation`.
 */
final class HeadingOrderCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.heading.order';
    }

    public static function name(): string
    {
        return 'Heading structure';
    }

    public static function rules(): array
    {
        return ['heading-missing-h1', 'heading-multiple-h1', 'heading-skipped-level'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $headings = [];
        foreach ($xpath->query('//h1|//h2|//h3|//h4|//h5|//h6') as $node) {
            $headings[] = [
                'level' => (int) substr($node->nodeName, 1),
                'text' => $this->snippet($node->textContent),
            ];
        }

        $violations = [];

        $h1s = array_values(array_filter($headings, fn ($h) => $h['level'] === 1));
        if ($h1s === []) {
            $violations[] = $this->issue('heading-missing-h1', Violation::ERROR);
        } elseif (count($h1s) > 1) {
            // The second h1 is the one to fix; the page title's h1 stays.
            $violations[] = $this->issue('heading-multiple-h1', Violation::ERROR, $h1s[1]['text']);
        }

        $previousLevel = 0;
        foreach ($headings as $h) {
            if ($previousLevel > 0 && $h['level'] > $previousLevel + 1) {
                $violations[] = $this->issue(
                    'heading-skipped-level',
                    Violation::ERROR,
                    "h{$h['level']}: {$h['text']}",
                );
            }
            $previousLevel = $h['level'];
        }

        return $violations;
    }

    /**
     * Always full. Headings are ordinary markup and every page has an outline,
     * even when that outline is empty: a page with no h1 is a finding, not a
     * blind spot.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        return Coverage::full(self::key(), self::name());
    }
}
