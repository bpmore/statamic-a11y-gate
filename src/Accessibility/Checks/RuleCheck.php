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
 * Thinner than Windrow's version by one method. There, `blockMeta()` walks up to
 * the `data-block-uid` its renderer stamps, so the editor can highlight the block
 * that produced an issue and a violation can name the field to focus. A Statamic
 * entry rendered through the site's own templates carries no such attribute, so
 * that walk could only ever return empty strings here, and every check would be
 * calling it to pass nothing anywhere.
 */
abstract class RuleCheck implements Check
{
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
