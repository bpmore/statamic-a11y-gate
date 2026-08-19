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
     * How much of what this check judges it could see in this document.
     *
     * Every check answers, always. It used to be allowed to return null, which
     * meant "an opt-in check on a site that has not integrated me", and that was
     * a rule stated in this docblock and enforced nowhere. The type let any
     * check return null, and a check that vanished took the denominator with it:
     * `CheckReport::summary()` counts the entries it was handed, so a report
     * missing one says "4 of 4 checks ran in full" rather than 4 of 5. A check
     * that can stay quiet when it is blind looks exactly like a clean page.
     *
     * Whether a site wants to hear about an opt-in check it never integrated is
     * a question about the site, not about the document, so it is answered once
     * by the checker rather than re-derived here. See `needsOptIn()`.
     */
    public function coverage(DOMXPath $xpath): Coverage;

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
