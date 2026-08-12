<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The empty-alt branch of the image rule, which the shared corpus does not reach.
 *
 * Found by mutation: deleting the whole `alt=""` clause from `ImageAltCheck` left
 * the corpus suite green, because every fixture in `corpus/` either omits the
 * attribute or fills it in. That is a hole in a shared artifact rather than a bug
 * in this port, and closing it properly means a corpus case added to both
 * repositories in one change. Until that happens, this file guards the branch on
 * this side, so the gap is covered somewhere rather than only written down.
 *
 * `alt=""` is the distinction that matters most in practice: it is what an editor
 * saves before a description has been written, and it is also how a genuinely
 * decorative image opts out. Treating those two the same in either direction is a
 * real failure. Passing the first would let an undescribed image through the gate;
 * refusing the second would demand a description for a spacer.
 */
function imageFindings(string $html): array
{
    return array_map(
        fn (Violation $v) => [$v->rule, $v->severity],
        (new StaticAccessibilityChecker)->check($html),
    );
}

it('refuses an image whose alt is empty and not marked decorative', function () {
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" alt=""></body></html>'))
        ->toBe([['image-missing-alt', Violation::ERROR]]);
});

it('refuses an image with whitespace where a description should be', function () {
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" alt="   "></body></html>'))
        ->toBe([['image-missing-alt', Violation::ERROR]]);
});

it('lets a decorative image opt out with an empty alt', function () {
    // Both spellings, because a rule that accepted one and not the other would
    // send an author to add a description to a spacer image.
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" alt="" role="presentation"></body></html>'))
        ->toBe([]);

    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" alt="" aria-hidden="true"></body></html>'))
        ->toBe([]);
});

it('still refuses an image with no alt attribute even when marked decorative', function () {
    // role="presentation" without alt="" is not an opt-out: the attribute is what
    // says the omission was a decision rather than an oversight.
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" role="presentation"></body></html>'))
        ->toBe([['image-missing-alt', Violation::ERROR]]);
});
