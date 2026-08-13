<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * The conformance level the checks are run at.
 *
 * One family reads it: target size, which is 24 CSS pixels at AA (WCAG 2.5.8)
 * and 44 at AAA (2.5.5). Everything else in the pack is at the AA floor and
 * level-independent, so it ignores the argument entirely.
 *
 * A named constructor per level rather than a `config()` catalogue, because the
 * checker must stay framework-free: it takes HTML and returns findings. A list
 * you can read in ten seconds, and adding a level is a method rather than a
 * registry plus a resolver.
 *
 * It carries only what something here reads. Axe tag lists and contrast
 * thresholds belong to a browser pass and a token-time validator this addon does
 * not have, and a field nothing reads is an invitation to write code that
 * pretends to read it.
 */
final class AccessibilityStandard
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly int $targetSize,
    ) {}

    /** The enforced floor, and the default everywhere. */
    public static function wcag22aa(): self
    {
        return new self('wcag22aa', 'WCAG 2.2 Level AA', 24);
    }

    /**
     * A higher bar for the one criterion this checker can measure statically.
     *
     * Not a claim of AAA conformance, and the label must never be shortened to
     * one: WCAG itself advises against requiring AAA site-wide, and most AAA
     * criteria are judged on meaning by a person.
     */
    public static function wcag22aaa(): self
    {
        return new self('wcag22aaa', 'WCAG 2.2 Level AAA', 44);
    }
}
