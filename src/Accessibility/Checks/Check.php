<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMXPath;

/**
 * One family of accessibility rules, run against a parsed document.
 *
 * A check is pure: it reads a document and returns violations. It never writes,
 * never calls a model, never touches the framework. Purity is what lets the same
 * class serve a publish gate and a command-line pass without knowing which
 * called it, and it is what makes each one testable against fixtures alone.
 */
interface Check
{
    /** Stable identifier: 'a11y.link.purpose'. Never renamed. */
    public static function key(): string;

    /** What this family is called when an author is told whether it ran. */
    public static function name(): string;

    /**
     * Whether this check reads markup a host has to opt in to stamping.
     *
     * A check like this finds nothing on a site that has not integrated it, and
     * saying so on every page forever is noise rather than honesty: an author
     * who has no plain-language summaries does not need telling, on every entry,
     * that summaries were not checked. The opt-in list is a setting, and the
     * addon's config and README name these checks in one place instead.
     */
    public static function needsOptIn(): bool;

    /**
     * How much of what this check judges it could see in this document, or null
     * when it has nothing to report on this site at all.
     *
     * Null is only for an opt-in check the site has not integrated. Everything
     * else answers, including the checks that always work, because a check that
     * could stay quiet when it is blind looks exactly like a clean page.
     *
     * @param  array<int, string>  $optedIn  keys of the opt-in checks this site stamps for
     */
    public function coverage(DOMXPath $xpath, array $optedIn = []): ?Coverage;

    /**
     * The rules this check can raise, by their `Violation::rule` value.
     *
     * Declared so a test can assert the pack covers every rule without running
     * it. A rule that no check claims is a rule that quietly stopped being
     * checked, and the corpus test uses this list to prove every rule has a
     * fixture.
     *
     * @return array<int, string>
     */
    public static function rules(): array;

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array;
}
