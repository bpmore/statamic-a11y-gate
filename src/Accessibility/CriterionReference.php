<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * Where an author can read what a cited success criterion requires: the
 * W3C's Understanding page for it, under WCAG 2.2, which is the only version
 * this checker runs against.
 *
 * Only the criteria the rule table cites are listed, and a test holds the two
 * together: a rule that starts citing a criterion not listed here fails the
 * suite rather than shipping a finding with nowhere to go. The link is the
 * W3C's text and not a paraphrase of this addon's own, because a summary here
 * would be one more place a criterion could be misdescribed with the gate's
 * name on it. A house rule cites no criterion and gets no reference, for the
 * same reason it carries a plain name: the link would cite what the check
 * cannot establish.
 */
final class CriterionReference
{
    /** @var array<string, array{0: string, 1: string}> number => name, W3C page slug */
    private const CITED = [
        '1.1.1' => ['Non-text Content', 'non-text-content'],
        '1.2.1' => ['Audio-only and Video-only (Prerecorded)', 'audio-only-and-video-only-prerecorded'],
        '1.2.2' => ['Captions (Prerecorded)', 'captions-prerecorded'],
        '2.4.4' => ['Link Purpose (In Context)', 'link-purpose-in-context'],
        '2.5.8' => ['Target Size (Minimum)', 'target-size-minimum'],
        '4.1.2' => ['Name, Role, Value', 'name-role-value'],
    ];

    /**
     * The reference for a finding's label, or null for a house rule.
     *
     * @return array{number: string, name: string, url: string}|null
     */
    public static function for(string $label): ?array
    {
        if (! preg_match('/^WCAG (\d+\.\d+\.\d+)$/', $label, $m) || ! isset(self::CITED[$m[1]])) {
            return null;
        }

        [$name, $slug] = self::CITED[$m[1]];

        return [
            'number' => $m[1],
            'name' => $name,
            'url' => "https://www.w3.org/WAI/WCAG22/Understanding/{$slug}.html",
        ];
    }

    /** @return array<int, string> */
    public static function numbers(): array
    {
        return array_keys(self::CITED);
    }
}
