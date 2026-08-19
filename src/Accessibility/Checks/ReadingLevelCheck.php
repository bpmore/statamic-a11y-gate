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
    /** The attribute this check reads, for the rule and for its coverage. */
    private const STAMPED = '//*[@data-a11y-reading-grade]';

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

        foreach ($xpath->query(self::STAMPED) as $node) {
            $grade = (float) $node->getAttribute('data-a11y-reading-grade');

            if ($grade > ReadingLevel::PLAIN_MAX_GRADE) {
                $violations[] = $this->issue(
                    'reading-level-high',
                    'reading grade '.rtrim(rtrim(number_format($grade, 1), '0'), '.'),
                );
            }
        }

        return $violations;
    }

    public static function needsOptIn(): bool
    {
        return true;
    }

    /**
     * Full where the site stamps a grade, and `none` where it does not.
     *
     * This is the check the opt-in setting was added for. A summary is just more
     * text on the finished page and nothing marks out which words were meant to
     * be the plain ones, so on a site with no summaries there is no page where
     * this could ever say anything useful, and a standing notice saying so would
     * be read once and scrolled past forever after.
     *
     * That suppression is the checker's job now, not this method's. See
     * `Check::needsOptIn()`, which used to declare it while an `in_array` here
     * quietly did it.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        $stamped = $xpath->query(self::STAMPED);

        if ($stamped !== false && $stamped->length > 0) {
            return Coverage::full(self::key(), self::name());
        }

        return Coverage::none(
            self::key(),
            self::name(),
            'A plain-language summary is just more text on the finished page, and nothing on this page was marked up as one.',
        );
    }
}
