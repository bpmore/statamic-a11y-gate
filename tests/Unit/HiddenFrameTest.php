<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The hidden-frame exemption on the embed-title rule, edge by edge.
 *
 * The corpus case (video-hidden-frame) pins the decision on one page carrying
 * every spelling at once. These pin each spelling alone, so a regression says
 * which one broke, and they pin the two directions the corpus cannot: a visible
 * untitled frame still refuses, and hiding that only a stylesheet knows about
 * does not count as hiding.
 */
function frameFindings(string $html): array
{
    return array_map(
        fn (Violation $v) => [$v->rule, $v->severity],
        (new StaticAccessibilityChecker)->check('<html lang="en"><body><h1>Watch</h1>'.$html.'</body></html>'),
    );
}

it('still refuses a visible untitled frame', function () {
    // The mutation check for every exemption below: the rule itself must hold.
    expect(frameFindings('<iframe src="https://example.com/embed/1"></iframe>'))
        ->toBe([['video-missing-title', Violation::ERROR]]);
});

it('exempts a frame inside noscript', function () {
    // Google Tag Manager's install snippet. Before the exemption, every site
    // carrying GTM could not publish anything at all.
    expect(frameFindings('<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-X"></iframe></noscript>'))
        ->toBe([]);
});

it('exempts an aria-hidden frame, on itself or an ancestor', function () {
    expect(frameFindings('<iframe src="/w" aria-hidden="true"></iframe>'))->toBe([]);
    expect(frameFindings('<div aria-hidden="true"><iframe src="/w"></iframe></div>'))->toBe([]);
});

it('exempts a frame hidden by inline style, on itself or an ancestor', function () {
    expect(frameFindings('<iframe src="/w" style="display: none"></iframe>'))->toBe([]);
    expect(frameFindings('<div style="visibility:hidden"><iframe src="/w"></iframe></div>'))->toBe([]);
});

it('exempts a frame declared zero-size in its own attributes', function () {
    expect(frameFindings('<iframe src="/w" width="0" height="0"></iframe>'))->toBe([]);
});

it('does not treat a stylesheet class as hiding', function () {
    // Only inline evidence counts. A class that hides is invisible to a pass
    // over markup, and guessing would err on the side of not checking, which
    // for a gate is the wrong side.
    expect(frameFindings('<iframe src="/w" class="hidden"></iframe>'))
        ->toBe([['video-missing-title', Violation::ERROR]]);
});

it('keeps the captions notice off a page whose only media is hidden frames', function () {
    // Coverage uses the same exemption: a page whose only "media" is a tracking
    // pixel has no video, and a standing "confirm captions" notice about it
    // would be noise an author learns to scroll past.
    $checker = new StaticAccessibilityChecker;

    $tracking = $checker->report('<html lang="en"><body><h1>W</h1><noscript><iframe src="/t"></iframe></noscript></body></html>');
    expect($tracking->notices())->toBe([]);

    $video = $checker->report('<html lang="en"><body><h1>W</h1><iframe title="A film" src="/v"></iframe></body></html>');
    expect(count($video->notices()))->toBeGreaterThan(0, 'a visible video should still carry the captions notice');
});
