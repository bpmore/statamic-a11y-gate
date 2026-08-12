<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\Checks\CheckPack;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;

/**
 * What the checker says about how much of a page it could see.
 *
 * The rule this file guards is the one in CLAUDE.md that everything else rests
 * on: a check that could not run must say so, because a silent zero is
 * indistinguishable from a pass.
 */
function coverageFor(string $html): array
{
    $report = (new StaticAccessibilityChecker)->report($html);
    $byKey = [];

    foreach ($report->coverage as $entry) {
        $byKey[$entry->check] = $entry;
    }

    return $byKey;
}

const ORDINARY_PAGE = '<html lang="en"><body><h1>The weir</h1><p><a href="/about">About the weir</a></p></body></html>';

it('reports every check in the pack, every time', function () {
    // The assertion that keeps this honest as families are added: a new check
    // that forgets to declare its coverage is a check that goes quiet without
    // saying so, which is the failure this whole file exists to prevent.
    $coverage = coverageFor(ORDINARY_PAGE);

    expect(array_keys($coverage))->toHaveCount(count(CheckPack::CHECKS));

    foreach (CheckPack::CHECKS as $class) {
        expect(array_key_exists($class::key(), $coverage))
            ->toBeTrue("the {$class::key()} check did not report its coverage");
    }
});

it('says the two host-markup checks could not run on an ordinary page', function () {
    // These read attributes only a host that opts in will stamp. On any normal
    // Statamic site they find nothing, and "found nothing" must never be
    // presentable as "nothing is wrong".
    $coverage = coverageFor(ORDINARY_PAGE);

    expect($coverage['a11y.link.unpublished']->extent)->toBe(Coverage::NONE);
    expect($coverage['a11y.text.reading_level']->extent)->toBe(Coverage::NONE);

    expect($coverage['a11y.link.unpublished']->limit)->not->toBe('');
    expect($coverage['a11y.text.reading_level']->limit)->not->toBe('');
});

it('says target size and media alternatives only ever ran partly', function () {
    // Target size reads sizes written into the markup, and nearly every site
    // sizes its controls in a stylesheet. Media alternatives checks embed
    // titles and nothing else without host markup. Both are partial on every
    // page, including a page where they found something.
    $coverage = coverageFor(ORDINARY_PAGE);

    expect($coverage['a11y.target.size']->extent)->toBe(Coverage::PARTIAL);
    expect($coverage['a11y.media.alternatives']->extent)->toBe(Coverage::PARTIAL);
});

it('says the three portable checks ran in full', function () {
    $coverage = coverageFor(ORDINARY_PAGE);

    expect($coverage['a11y.heading.order']->extent)->toBe(Coverage::FULL);
    expect($coverage['a11y.link.purpose']->extent)->toBe(Coverage::FULL);
    expect($coverage['a11y.media.alt_missing']->extent)->toBe(Coverage::FULL);
});

it('upgrades a host-markup check to full when the site does mark it up', function () {
    // The other half of the claim. Saying a check cannot run here has to be a
    // reading of the page, not a fixed opinion about Statamic, or the reporting
    // would be as blind as the thing it describes.
    $coverage = coverageFor(
        '<html lang="en"><body><h1>The weir</h1><p data-windrow-reading-grade="7.2">Short words.</p></body></html>'
    );

    expect($coverage['a11y.text.reading_level']->extent)->toBe(Coverage::FULL);
    expect($coverage['a11y.text.reading_level']->limit)->toBe('');
});

it('counts what ran in a sentence, and never scores the page', function () {
    $summary = (new StaticAccessibilityChecker)->report(ORDINARY_PAGE)->summary();

    expect($summary)->toBe('3 of 7 checks ran in full, 2 ran partly, 2 could not run here.');

    // The refusals this product is sold on. No percentage, no grade, no claim.
    foreach (['%', 'score', 'compliant', 'accessible'] as $forbidden) {
        expect(str_contains(strtolower($summary), $forbidden))
            ->toBeFalse("the coverage summary must never contain '{$forbidden}'");
    }
});

it('keeps a clean page from looking like a fully checked page', function () {
    // The specific lie this feature exists to stop. A page with no findings and
    // no coverage line reads as "nothing is wrong here". Four of seven families
    // are blind or half-blind, and the sentence has to say so on a clean page
    // just as loudly as on a broken one.
    $report = (new StaticAccessibilityChecker)->report(ORDINARY_PAGE);

    expect($report->violations)->toBe([]);
    expect($report->summary())->toContain('could not run here');
});
