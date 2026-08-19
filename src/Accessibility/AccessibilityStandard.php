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
 * **An enum rather than a value object, and the backing value is the config
 * string.** It used to be a class holding a key, a label and a size, all three
 * settable by anyone: `new AccessibilityStandard('wcag22aa', 'WCAG 2.2 Level
 * AAA', 7)` type-checked everywhere the class is hinted, and would have enforced
 * a 7 pixel minimum under a label claiming AAA. In an addon whose value is that
 * its claims are true, a name that can disagree with the number it is enforcing
 * is the wrong shape. Two of the three fields were also read by nothing, which
 * this file's own rule says not to do.
 *
 * Derived rather than stored, so the two cannot drift, and a level is still one
 * case plus two match arms rather than a registry and a resolver.
 */
enum AccessibilityStandard: string
{
    /** The enforced floor, and the default everywhere. */
    case Wcag22aa = 'wcag22aa';

    /**
     * A higher bar for the one criterion this checker can measure statically.
     *
     * Not a claim of AAA conformance, and the label must never be shortened to
     * one: WCAG itself advises against requiring AAA site-wide, and most AAA
     * criteria are judged on meaning by a person.
     */
    case Wcag22aaa = 'wcag22aaa';

    public function label(): string
    {
        return match ($this) {
            self::Wcag22aa => 'WCAG 2.2 Level AA',
            self::Wcag22aaa => 'WCAG 2.2 Level AAA',
        };
    }

    /** The minimum touch target, in CSS pixels. */
    public function targetSize(): int
    {
        return match ($this) {
            self::Wcag22aa => 24,
            self::Wcag22aaa => 44,
        };
    }
}
