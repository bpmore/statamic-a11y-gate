<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMNode;
use DOMXPath;

/**
 * Time-based and non-text media that is missing its text alternative.
 *
 * WCAG 1.2.2 for captions, 1.2.1 for transcripts, house rules for the rest.
 * Wider than the name suggests and inherited that way: it also carries a
 * figure's required text version and broken footnote references.
 *
 * **Four of its five rules read attributes a host has to stamp**, and on an
 * ordinary Statamic site nothing stamps them, so those four find nothing. That
 * is not a pass and the addon must never present it as one. The attribute names
 * are Windrow's (`data-windrow-*`) rather than renamed, because the shared
 * corpus fixtures use them and renaming them here would fork the corpus to no
 * purpose.
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
        foreach ($xpath->query('//*[@data-windrow-video-captions="missing"]') as $node) {
            $violations[] = $this->issue(
                'video-captions-unconfirmed',
                Violation::ERROR,
                $this->frameLabel($node),
            );
        }

        // A transcript is a link on this page rather than something held
        // elsewhere, so it is demanded rather than attested.
        foreach ($xpath->query('//*[@data-windrow-audio-transcript="missing"]') as $node) {
            $violations[] = $this->issue(
                'audio-transcript-missing',
                Violation::ERROR,
                $this->frameLabel($node),
            );
        }

        // A figure without its text version: the informative-image-without-alt
        // class of failure. Presence-only and labelled honestly, because the
        // checker can prove the text exists and the author owns whether it is
        // faithful to the image.
        foreach ($xpath->query('//*[@data-windrow-figure-text="missing"]') as $node) {
            $violations[] = $this->issue('figure-text-missing', Violation::ERROR);
        }

        // Broken footnotes have to be stamped from the source, because the
        // rendered page cannot show them honestly: an unresolved reference
        // renders as literal "[^x]" junk, and an unreferenced note is dropped by
        // the renderer, so the author's words vanish with no visible trace.
        foreach ($xpath->query('//*[@data-windrow-footnotes="broken"]') as $node) {
            $violations[] = $this->issue('footnotes-broken', Violation::ERROR);
        }

        // An embedded player is an iframe, so its accessible name comes from the
        // title attribute and there is nothing else to read it from. Deliberately
        // not scoped to video providers, so any future embed inherits it.
        foreach ($xpath->query('//iframe') as $iframe) {
            if (trim($iframe->getAttribute('title')) === '') {
                $violations[] = $this->issue(
                    'video-missing-title',
                    Violation::ERROR,
                    $iframe->getAttribute('src'),
                );
            }
        }

        return $violations;
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
     */
    public function coverage(DOMXPath $xpath, array $optedIn = []): ?Coverage
    {
        $media = $xpath->query('//iframe|//video|//audio|//figure|//object|//embed');

        if ($media === false || $media->length === 0) {
            return Coverage::full(self::key(), self::name());
        }

        return Coverage::partial(
            self::key(),
            self::name(),
            'Embed titles were checked. Captions, transcripts, figure text and footnotes are only checked on sites that mark them up, and this one does not.',
            'Captions were not checked. Only you can confirm this video or recording has them.',
        );
    }
}
