<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * Maps each rule to the label it may cite and the plain-language explanation and
 * call to action an author is shown. This is the only place a rule id becomes
 * guidance, so the wording cannot differ between two rule families.
 *
 * The label attached to each rule is pinned by the corpus. Changing one is a
 * behaviour change, not a wording change, because the label is what a finding is
 * allowed to cite and citing the wrong thing is the failure this product cannot
 * afford.
 */
final class Remediation
{
    /**
     * @var array<string, array{wcag: string, message: string, cta: string}>
     */
    public const RULES = [
        // The three heading rules are a house standard, not WCAG, and they are
        // named as one. No success criterion covers any of them:
        //
        //   - No criterion requires a page to have an h1.
        //   - HTML permits more than one h1; no criterion forbids it.
        //   - Skipping a level fails no criterion either. G141 (organising with
        //     headings) is a *sufficient technique* for 1.3.1, and not using a
        //     sufficient technique is not a failure of the criterion.
        //
        // axe-core agrees: `page-has-heading-one` and `heading-order` are tagged
        // best-practice with no WCAG tag, and there is no rule for multiple h1.
        //
        // They still refuse a publish, which is a defensible house rule: a single
        // clear h1 and an unbroken outline make a page better to listen to.
        // Blocking is the product's call. Attributing that call to WCAG is not:
        // it misleads the author, and a procurement team that checks the citation
        // finds nothing behind it.
        'heading-missing-h1' => [
            'wcag' => 'Heading structure',
            'message' => 'This page has no main heading, so people using screen readers can\'t tell what it\'s about.',
            'cta' => 'Add a heading',
        ],
        'heading-multiple-h1' => [
            'wcag' => 'Heading structure',
            'message' => 'This page has more than one main heading. A page should have a single top-level heading.',
            'cta' => 'Lower the extra heading',
        ],
        'heading-skipped-level' => [
            'wcag' => 'Heading structure',
            'message' => 'A heading skips a level, which breaks the outline for screen-reader users.',
            'cta' => 'Fix the heading level',
        ],
        'link-empty' => [
            'wcag' => 'WCAG 2.4.4',
            'message' => 'A link has no text, so people can\'t tell where it goes.',
            'cta' => 'Add link text',
        ],
        'link-unclear' => [
            'wcag' => 'WCAG 2.4.4',
            'message' => 'Link text like "click here" or a bare web address doesn\'t describe where the link goes.',
            'cta' => 'Use descriptive link text',
        ],
        'link-vague' => [
            'wcag' => 'WCAG 2.4.4',
            'message' => 'This link text is vague out of context. Consider describing the destination.',
            'cta' => 'Make the link text clearer',
        ],
        'button-empty' => [
            'wcag' => 'WCAG 4.1.2',
            'message' => 'A button has no accessible name, so people using screen readers can\'t tell what it does.',
            'cta' => 'Add button text or an aria-label',
        ],
        'image-missing-alt' => [
            'wcag' => 'WCAG 1.1.1',
            'message' => 'This image has no description, so people using screen readers don\'t know what it shows.',
            'cta' => 'Add a description',
        ],
        'target-size-minimum' => [
            'wcag' => 'WCAG 2.5.8',
            'message' => 'An interactive control is smaller than 24×24 pixels, which is hard to hit on a touch screen.',
            // Earlier wording offered "or add spacing". The criterion does allow
            // that, but this check only measures size, so an author who added
            // spacing would watch the gate stay red with no idea why. Name the fix
            // that actually clears it.
            'cta' => 'Make the control at least 24×24',
        ],
        // Not a WCAG citation and deliberately not dressed as one: no criterion
        // covers "this link has no destination". A reader who tabs to it, hears
        // "link", and activates it gets nothing back, which is a real barrier,
        // just not one WCAG names.
        //
        // A warning rather than a refusal, because an author part-way through a
        // page may legitimately have a placeholder in it, and refusing a publish
        // over a draft state the editor itself allows is how an optional field
        // ends up blocking a publish.
        'link-goes-nowhere' => [
            'wcag' => 'Link check',
            'message' => 'A link on this page has no destination, so nothing happens when someone follows it.',
            'cta' => 'Point the link somewhere, or remove it',
        ],
        // Same family, same reason for the plain label. A warning because two
        // pages can reference each other: refusing both would deadlock the pair,
        // and an author staging a launch would have no way forward but to unpick
        // their own links.
        'link-unpublished-page' => [
            'wcag' => 'Link check',
            'message' => 'A button on this block opens a page that has not been published yet, so visitors would reach a "page not found".',
            'cta' => 'Publish that page too, or point the button somewhere else',
        ],
        'video-captions-unconfirmed' => [
            'wcag' => 'WCAG 1.2.2',
            'message' => 'This video has not been confirmed to have captions, so people who are deaf or hard of hearing may miss the spoken content.',
            'cta' => 'Confirm captions',
        ],
        'reading-level-high' => [
            'wcag' => 'Reading level (guide)',
            'message' => 'This plain-language summary still reads as hard as the page it is summarising. Shorter sentences and more everyday words would help.',
            'cta' => 'Simplify the summary',
        ],
        'audio-transcript-missing' => [
            'wcag' => 'WCAG 1.2.1',
            'message' => 'This recording has no transcript, so anyone who is deaf or hard of hearing gets nothing from it at all.',
            'cta' => 'Add a transcript link',
        ],
        'figure-text-missing' => [
            'wcag' => 'Figure check',
            'message' => 'This figure has no text version, so its content is a picture and nothing else to anyone using a screen reader.',
            'cta' => 'Write the text version',
        ],
        'footnotes-broken' => [
            'wcag' => 'Footnote check',
            'message' => 'A footnote reference has no note, or a note has no reference. Either way part of the text goes missing on the published page.',
            'cta' => 'Match every footnote to its reference',
        ],
        'video-missing-title' => [
            'wcag' => 'WCAG 4.1.2',
            'message' => 'This embedded video has no title, so people using screen readers can\'t tell what it is.',
            'cta' => 'Add a video title',
        ],
    ];

    public static function violation(string $rule, string $severity, string $pointer = ''): Violation
    {
        $r = self::RULES[$rule];

        return new Violation(
            $rule,
            $severity,
            $r['wcag'],
            $r['message'],
            $r['cta'],
            $pointer,
        );
    }
}
