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

        foreach ($xpath->query('//*[@data-a11y-reading-grade]') as $node) {
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
     * Full where the site stamps a grade, nothing to report on a site that has
     * not integrated this, and `none` on a site that has but did not stamp this
     * page.
     *
     * This is the check the opt-in setting was added for. A summary is just more
     * text on the finished page and nothing marks out which words were meant to
     * be the plain ones, so on a site with no summaries there is no page where
     * this could ever say anything useful.
     */
    public function coverage(DOMXPath $xpath, array $optedIn = []): ?Coverage
    {
        $stamped = $xpath->query('//*[@data-a11y-reading-grade]');

        if ($stamped !== false && $stamped->length > 0) {
            return Coverage::full(self::key(), self::name());
        }

        if (! in_array(self::key(), $optedIn, true)) {
            return null;
        }

        return Coverage::none(
            self::key(),
            self::name(),
            'A plain-language summary is just more text on the finished page, and nothing on this page was marked up as one.',
        );
    }
}
