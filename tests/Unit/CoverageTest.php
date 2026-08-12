<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;

/**
 * What the checker says about how much of a page it could see.
 *
 * The rule this guards is the one in CLAUDE.md that everything else rests on: a
 * check that could not run must say so, because a silent zero is
 * indistinguishable from a pass.
 *
 * The rule it also guards, which is younger and was learned from looking at the
 * panel, is that this must be said about **the page in front of the author**. A
 * standing notice that captions were not checked, on a site with no videos, is
 * not honesty. It is the line people learn to scroll past, and they take the
 * real one with them when they go.
 */
function coverageFor(string $html, array $optedIn = []): array
{
    $report = (new StaticAccessibilityChecker)->report($html, null, $optedIn);
    $byKey = [];

    foreach ($report->coverage as $entry) {
        $byKey[$entry->check] = $entry;
    }

    return $byKey;
}

const ORDINARY_PAGE = '<html lang="en"><body><h1>The weir</h1><p><a href="/about">About the weir</a></p></body></html>';

const PAGE_WITH_A_VIDEO = '<html lang="en"><body><h1>The weir</h1><iframe title="The weir in flood" src="/v"></iframe></body></html>';

it('says nothing about the opt-in checks on a site that has not opted in', function () {
    // The noise this setting exists to remove. An author with no plain-language
    // summaries does not need telling, on every entry forever, that summaries
    // were not checked.
    $coverage = coverageFor(ORDINARY_PAGE);

    expect(array_key_exists('a11y.link.unpublished', $coverage))->toBeFalse();
    expect(array_key_exists('a11y.text.reading_level', $coverage))->toBeFalse();
});

it('reports an opt-in check the site did integrate, and says the page carried none of it', function () {
    // The case worth having. A site that says it marks up its unpublished links,
    // on a page where nothing was marked, has a real gap rather than a habit.
    $coverage = coverageFor(ORDINARY_PAGE, ['a11y.link.unpublished']);

    expect($coverage['a11y.link.unpublished']->extent)->toBe(Coverage::NONE);
    expect($coverage['a11y.link.unpublished']->limit)->not->toBe('');
});

it('reports an opt-in check as full when the page carries its markup, opted in or not', function () {
    // Findings arrive whether or not the setting mentions the check, so coverage
    // has to as well. A site that starts stamping before it edits a config file
    // gets told what ran, not silence.
    $coverage = coverageFor(
        '<html lang="en"><body><h1>The weir</h1><p data-windrow-reading-grade="7.2">Short words.</p></body></html>'
    );

    expect($coverage['a11y.text.reading_level']->extent)->toBe(Coverage::FULL);
    expect($coverage['a11y.text.reading_level']->limit)->toBe('');
});

it('treats a page with no media as fully checked for media', function () {
    // The correction that started this. A page with no video, no audio and no
    // figures has no captions that could have been missed, exactly as a page
    // with no images has no descriptions that could have been missed.
    expect(coverageFor(ORDINARY_PAGE)['a11y.media.alternatives']->extent)->toBe(Coverage::FULL);
});

it('says a page with a video was only partly checked for media', function () {
    // And the other half. Where there IS a video, the hole is real and specific:
    // its title was checked and its captions cannot be.
    $coverage = coverageFor(PAGE_WITH_A_VIDEO);

    expect($coverage['a11y.media.alternatives']->extent)->toBe(Coverage::PARTIAL);
    expect($coverage['a11y.media.alternatives']->limit)->toContain('Captions');
});

it('treats a page with no controls as fully checked for target size', function () {
    expect(coverageFor(PAGE_WITH_A_VIDEO)['a11y.target.size']->extent)->toBe(Coverage::FULL);
});

it('says a page with a link was only partly checked for target size', function () {
    $coverage = coverageFor(ORDINARY_PAGE);

    expect($coverage['a11y.target.size']->extent)->toBe(Coverage::PARTIAL);
    expect($coverage['a11y.target.size']->limit)->toContain('stylesheet');
});

it('always reports the three checks that read nothing but ordinary markup', function () {
    foreach ([ORDINARY_PAGE, PAGE_WITH_A_VIDEO] as $html) {
        $coverage = coverageFor($html);

        expect($coverage['a11y.heading.order']->extent)->toBe(Coverage::FULL);
        expect($coverage['a11y.link.purpose']->extent)->toBe(Coverage::FULL);
        expect($coverage['a11y.media.alt_missing']->extent)->toBe(Coverage::FULL);
    }
});

it('counts what ran in a sentence, and never scores the page', function () {
    $summary = (new StaticAccessibilityChecker)->report(ORDINARY_PAGE)->summary();

    expect($summary)->toBe('4 of 5 checks ran in full, 1 ran partly.');

    // The refusals this product is sold on. No percentage, no grade, no claim.
    foreach (['%', 'score', 'compliant', 'accessible'] as $forbidden) {
        expect(str_contains(strtolower($summary), $forbidden))
            ->toBeFalse("the coverage summary must never contain '{$forbidden}'");
    }
});

it('still counts the gap out loud when there is one', function () {
    $summary = (new StaticAccessibilityChecker)
        ->report(ORDINARY_PAGE, null, ['a11y.link.unpublished'])
        ->summary();

    expect($summary)->toBe('4 of 6 checks ran in full, 1 ran partly, 1 could not run here.');
});

it('keeps a clean page from looking like a fully checked page', function () {
    // The specific lie this feature exists to stop. A page with no findings and
    // no coverage line reads as "nothing is wrong here", and on a page carrying
    // a video that reading is false.
    $report = (new StaticAccessibilityChecker)->report(PAGE_WITH_A_VIDEO);

    expect($report->violations)->toBe([]);
    expect($report->summary())->toContain('ran partly');
});
