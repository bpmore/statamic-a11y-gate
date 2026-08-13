<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\Remediation;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The helpers every rule family in the pack shares.
 *
 * A base class rather than a trait or an injected collaborator, because these
 * methods have no state, no configuration and no reason to vary. Making them
 * overridable would be offering a choice nobody should make.
 *
 * There is no way to point at the block that produced a finding, and no attempt
 * to invent one. An editor that stamped `data-block-uid` into its own output
 * could walk up to it and highlight the block. A Statamic entry rendered through
 * the site's own templates carries no such attribute, so
 * that walk could only ever return empty strings here, and every check would be
 * calling it to pass nothing anywhere.
 */
abstract class RuleCheck implements Check
{
    /**
     * Most checks read ordinary markup and need nothing from the host.
     */
    public static function needsOptIn(): bool
    {
        return false;
    }

    /**
     * Build a violation, resolving its remediation copy. Every check emits
     * through here, so the shape cannot differ between rule families.
     */
    protected function issue(string $rule, string $severity, string $pointer = ''): Violation
    {
        return Remediation::violation($rule, $severity, $pointer);
    }

    protected function looksLikeUrl(string $text): bool
    {
        return (bool) preg_match('#^(https?://|www\.)#i', $text)
            || (bool) preg_match('#^[a-z0-9.-]+\.[a-z]{2,}(/|$)#i', $text);
    }

    protected function snippet(string $text): string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        return mb_strlen($text) > 60 ? mb_substr($text, 0, 57).'…' : $text;
    }
}
