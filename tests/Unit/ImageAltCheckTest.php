<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The empty-alt branch of the image rule, in more detail than the corpus pins.
 *
 * Found by mutation: deleting the whole `alt=""` clause from `ImageAltCheck` left
 * the corpus suite green, because every fixture in `corpus/` either omitted the
 * attribute or filled it in. That hole is now closed by `image-alt-empty` and
 * `image-decorative-opt-out`, added once this project stopped having to
 * coordinate a corpus change with anybody else.
 *
 * This file stays because it says more than the corpus does: whitespace-only alt,
 * and the case where a decorative marker appears with no alt attribute at all.
 * The corpus pins the decision; these pin the edges around it.
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

it('lets a decorative marker opt out even with no alt attribute at all', function () {
    // This used to be refused, on the argument that alt="" is what shows the
    // omission was a decision. Reversed: assistive tech honours the marker with
    // or without the attribute (axe passes both), so the refusal was citing
    // WCAG 1.1.1 against markup that does not fail it. Pinned in the corpus as
    // image-decorative-no-alt.
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" role="presentation"></body></html>'))
        ->toBe([]);

    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg" aria-hidden="true"></body></html>'))
        ->toBe([]);
});

it('still refuses a plain image with no alt attribute', function () {
    // The mutation check for the change above: loosening the decorative branch
    // must not loosen the rule itself.
    expect(imageFindings('<html lang="en"><body><h1>Weir</h1><img src="/a.jpg"></body></html>'))
        ->toBe([['image-missing-alt', Violation::ERROR]]);
});
