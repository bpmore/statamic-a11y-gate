<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\ReadingLevel;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMXPath;

/**
 * A plain-language summary that is not plain.
 *
 * The grade is stamped on the element by the host, because only the host has the
 * author's own words: by the time the HTML exists, the summary is just more text
 * on the page. Nothing stamps it on an ordinary Statamic site, so this rule finds
 * nothing there.
 *
 * A warning, and labelled "Reading level (guide)" rather than a criterion. WCAG
 * 3.1.5 is AAA and judged on meaning; Flesch-Kincaid counts sentence length and
 * vowel groups. It cannot tell "the trust will action the referral" from plain
 * English, and citing a criterion it cannot establish is the mistake this rule
 * exists to avoid repeating.
 */
final class ReadingLevelCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.text.reading_level';
    }

    public static function name(): string
    {
        return 'Plain-language summaries';
    }

    public static function rules(): array
    {
        return ['reading-level-high'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $violations = [];

        foreach ($xpath->query('//*[@data-windrow-reading-grade]') as $node) {
            $grade = (float) $node->getAttribute('data-windrow-reading-grade');

            if ($grade > ReadingLevel::PLAIN_MAX_GRADE) {
                $violations[] = $this->issue(
                    'reading-level-high',
                    Violation::WARN,
                    'reading grade '.rtrim(rtrim(number_format($grade, 1), '0'), '.'),
                );
            }
        }

        return $violations;
    }

    /**
     * Full only where the site stamps a grade, and nothing at all otherwise. By
     * the time the page exists the summary is just more text on it, and nothing
     * marks out which words were meant to be the plain ones.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        $stamped = $xpath->query('//*[@data-windrow-reading-grade]');

        return $stamped !== false && $stamped->length > 0
            ? Coverage::full(self::key(), self::name())
            : Coverage::none(
                self::key(),
                self::name(),
                'A plain-language summary is just more text on the finished page, so this is only checked on sites that mark those summaries up.',
            );
    }
}
