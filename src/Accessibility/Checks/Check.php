<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
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
