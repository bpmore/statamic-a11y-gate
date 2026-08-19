<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Time-based and non-text media that is missing its text alternative.
 *
 * WCAG 1.2.2 for captions, 1.2.1 for transcripts, house rules for the rest.
 * Wider than the name suggests and inherited that way: it also carries a
 * figure's required text version and broken footnote references.
 *
 * **Four of its five rules read `data-a11y-*` attributes a site has to stamp**,
 * and on an ordinary Statamic site nothing stamps them, so those four find
 * nothing. That is not a pass and the addon must never present it as one: the
 * corpus marks those fixtures `host-markup` so the difference between "checked
 * and clean" and "had nothing to read" cannot be lost.
 *
 * Only the iframe title rule reads ordinary HTML and runs anywhere.
 */
final class MediaAlternativesCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.media.alternatives';
    }

    public static function name(): string
    {
        return 'Video, audio and figures';
    }

    public static function rules(): array
    {
        return ['audio-transcript-missing', 'figure-text-missing', 'footnotes-broken', 'video-captions-unconfirmed', 'video-missing-title'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $violations = [];

        // Captions live at the video host, so the page cannot prove they exist.
        // The most an author can do is attest to them, and an unattested video is
        // reported as unconfirmed rather than as failing: the rule name says which.
        foreach ($xpath->query('//*[@data-a11y-video-captions="missing"]') as $node) {
            $violations[] = $this->issue(
                'video-captions-unconfirmed',
                $this->frameLabel($node),
            );
        }

        // A transcript is a link on this page rather than something held
        // elsewhere, so it is demanded rather than attested.
        foreach ($xpath->query('//*[@data-a11y-audio-transcript="missing"]') as $node) {
            $violations[] = $this->issue(
                'audio-transcript-missing',
                $this->frameLabel($node),
            );
        }

        // A figure without its text version: the informative-image-without-alt
        // class of failure. Presence-only and labelled honestly, because the
        // checker can prove the text exists and the author owns whether it is
        // faithful to the image.
        foreach ($xpath->query('//*[@data-a11y-figure-text="missing"]') as $node) {
            $violations[] = $this->issue('figure-text-missing');
        }

        // Broken footnotes have to be stamped from the source, because the
        // rendered page cannot show them honestly: an unresolved reference
        // renders as literal "[^x]" junk, and an unreferenced note is dropped by
        // the renderer, so the author's words vanish with no visible trace.
        foreach ($xpath->query('//*[@data-a11y-footnotes="broken"]') as $node) {
            $violations[] = $this->issue('footnotes-broken');
        }

        // An embedded player is an iframe, so its accessible name comes from the
        // title attribute and there is nothing else to read it from. Deliberately
        // not scoped to video providers, so any future embed inherits it.
        //
        // A frame nobody can perceive is exempt. Google Tag Manager's install
        // snippet is a zero-size display:none iframe inside <noscript>, which
        // this rule read as a video with no title, so every site carrying GTM
        // could not publish anything at all. Chat widgets do the same with
        // aria-hidden. A frame that is hidden from every visitor and every
        // screen reader has no accessible name to be missing.
        foreach ($xpath->query('//iframe') as $iframe) {
            if (! $iframe instanceof DOMElement || $this->hiddenFromEveryone($iframe)) {
                continue;
            }

            if (trim($iframe->getAttribute('title')) === '') {
                $violations[] = $this->issue(
                    'video-missing-title',
                    $iframe->getAttribute('src'),
                );
            }
        }

        return $violations;
    }

    /**
     * Whether no visitor and no screen reader will ever meet this element.
     *
     * Four spellings, each one seen on real tracking and widget embeds: an
     * ancestor <noscript> (GTM's install snippet), aria-hidden on the element
     * or an ancestor (chat widgets), inline display:none or visibility:hidden
     * (both, and the only kind of hiding a pass over markup can see: a
     * stylesheet class that hides is invisible here, same limit as target
     * size), and a frame declared zero-size in its own attributes (tracking
     * pixels).
     *
     * Only inline evidence counts, so a frame hidden by a stylesheet is still
     * checked. That errs toward checking, which for a gate is the safe side.
     */
    private function hiddenFromEveryone(DOMElement $el): bool
    {
        if ($el->getAttribute('width') === '0' || $el->getAttribute('height') === '0') {
            return true;
        }

        for ($node = $el; $node instanceof DOMElement; $node = $node->parentNode) {
            if ($node->nodeName === 'noscript' || $node->getAttribute('aria-hidden') === 'true') {
                return true;
            }

            $style = strtolower(preg_replace('/\s+/', '', $node->getAttribute('style')) ?? '');

            if (str_contains($style, 'display:none') || str_contains($style, 'visibility:hidden')) {
                return true;
            }
        }

        return false;
    }

    /**
     * A pointer a person can recognise: the embed's title, else its src, so the
     * author can tell which video the finding means.
     */
    private function frameLabel(DOMNode $node): string
    {
        if ($node->ownerDocument === null) {
            return '';
        }

        $xpath = new DOMXPath($node->ownerDocument);

        foreach ($xpath->query('.//iframe', $node) as $iframe) {
            $title = trim($iframe->getAttribute('title'));

            return $title !== '' ? $title : $iframe->getAttribute('src');
        }

        return '';
    }

    /**
     * Full on a page with no media at all, and partial on a page with any.
     *
     * One of its five rules reads ordinary markup: an embed needs a title, and
     * that is checked everywhere. The other four need the site to say what it
     * knows, because a page cannot show whether a video at YouTube carries
     * captions or whether a footnote lost its reference.
     *
     * So a page carrying a video has a real hole in it and is told so. A page
     * with no video, no audio and no figures has nothing this rule could have
     * missed, and saying otherwise would be the kind of standing warning people
     * learn to scroll past.
     *
     * Partial rather than full even where the site does stamp, because a checker
     * cannot confirm that everything which needed marking got marked.
     *
     * **Three separate questions, and they used to be answered by one count.**
     * Whether to say `full` turns on there being any media at all. Which limit
     * to state turns on whether this page carries the markup the other four
     * rules read. Whether the author gets a notice turns on there being
     * something playable, because captions are the only gap here that is theirs
     * to settle. Collapsing all three into "is any of the six elements visible"
     * made the check say two false things, both seen on real shapes of page:
     *
     * - a stamped page was told captions "are only checked on sites that mark
     *   them up, and this one does not", in the same report as a finding that
     *   only that markup could have produced;
     * - a page whose only media was a `figure` was told "only you can confirm
     *   this video or recording has them", about a video it does not have.
     *   `figure` is the commonest of the six on an ordinary Statamic site, so
     *   that was the standing notice this class exists to avoid.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        // Hidden frames are excluded throughout for the same reason they are
        // excluded from the title rule: a page whose only "media" is a tracking
        // iframe has no video, and telling its author to confirm captions on it
        // would be a standing notice about something that does not exist.
        $visible = $this->visible($xpath, '//iframe|//video|//audio|//figure|//object|//embed');

        if ($visible === 0) {
            return Coverage::full(self::key(), self::name());
        }

        // Any of the four attributes this check reads. Presence is enough: it
        // is what separates a site that has integrated these rules from one
        // that has not, which is the only thing the limit sentence claims.
        $stamped = $xpath->query(
            '//*[@data-a11y-video-captions or @data-a11y-audio-transcript'
            .' or @data-a11y-figure-text or @data-a11y-footnotes]'
        );

        $marksItsMarkup = $stamped !== false && $stamped->length > 0;

        // Time-based media only. A figure is not something anybody watches or
        // listens to, so it has no captions to confirm.
        $playable = $this->visible($xpath, '//iframe|//video|//audio|//object|//embed');

        // Captions specifically, not the other three. A transcript is demanded
        // rather than attested, and figure text and footnotes are proven
        // present or absent outright, so none of those three leaves a gap only
        // the author can close.
        $captions = $xpath->query('//*[@data-a11y-video-captions]');
        $captionsChecked = $captions !== false && $captions->length > 0;

        return Coverage::partial(
            self::key(),
            self::name(),
            $marksItsMarkup
                ? 'Embed titles were checked, and so was every caption, transcript, figure text and footnote this page marks up. A checker cannot confirm that everything which needed marking got marked.'
                : 'Embed titles were checked. Captions, transcripts, figure text and footnotes are only checked on sites that mark them up, and this one does not.',
            $playable > 0 && ! $captionsChecked
                ? 'Captions were not checked. Only you can confirm this video or recording has them.'
                : '',
        );
    }

    /**
     * How many elements matching this selector a visitor could actually meet.
     */
    private function visible(DOMXPath $xpath, string $selector): int
    {
        $nodes = $xpath->query($selector);

        $count = 0;

        foreach ($nodes === false ? [] : $nodes as $node) {
            if ($node instanceof DOMElement && ! $this->hiddenFromEveryone($node)) {
                $count++;
            }
        }

        return $count;
    }
}
