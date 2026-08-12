<?php

declare(strict_types=1);

use Statamic\Facades\User;

/**
 * The page under Tools that says what the addon checks and what it cannot.
 *
 * It carries the sentence this product is sold on being willing to say, so the
 * assertions here are mostly about words. That is not fussiness: the claim a
 * page it allows has not been proven accessible is the clause a buyer's lawyer
 * will read, and it now lives in exactly one place.
 */
beforeEach(function () {
    $this->user = User::make()->makeSuper();
    $this->user->save();
});

/**
 * The guide's text, flattened for matching.
 *
 * The control panel is an Inertia application, so a utility's HTML arrives
 * embedded in a JSON page object and every real newline in the template is a
 * literal backslash-n by the time it reaches the response. Collapsing only real
 * whitespace leaves those in place, and a phrase that happens to wrap in the
 * template fails to match for a reason that has nothing to do with the words.
 * Found by a test failing on "neither can ever go live" while the page said
 * exactly that.
 */
function guideText(string $html): string
{
    return (string) preg_replace('/\s+/', ' ', str_replace(['\\n', '\\t', '\\r'], ' ', $html));
}


it('is reachable under Tools', function () {
    $this->actingAs($this->user)
        ->get(cp_route('utilities.index').'/a11y-gate')
        ->assertOk();
});

it('refuses to call anything compliant or accessible', function () {
    $response = $this->actingAs($this->user)->get(cp_route('utilities.index').'/a11y-gate');

    $body = strtolower(guideText($response->getContent()));

    expect(str_contains($body, 'has not been proven accessible'))
        ->toBeTrue('the guide must say plainly that a clean result proves nothing');

    expect(str_contains($body, 'compliant'))
        ->toBeFalse('this addon never uses the word compliant');
});

it('says why each warning is a warning rather than that warnings do not matter', function () {
    // The page used to say warnings "are not worth blocking your work over",
    // which reads as the gate shrugging. Three rules do not block, each for a
    // reason worth more than the reassurance: no standard covers it, blocking
    // would deadlock a pair of pages, or the measurement is a guess.
    $body = guideText($this->actingAs($this->user)
        ->get(cp_route('utilities.index').'/a11y-gate')
        ->getContent());

    expect($body)->toContain('WCAG 2.2 AA stops the publish');
    expect($body)->toContain('neither can ever go live');
    expect($body)->toContain('blocking on a guess');
});

it('is drawn with the control panel components rather than borrowed styles', function () {
    // A utility view is compiled as a Vue template rather than printed as HTML,
    // so Statamic's components resolve here. The first version of this page was
    // a div with borrowed Tailwind classes and looked like a document dropped
    // beside the other utilities. Inside an accessibility product, reaching for
    // your own styles is the same mistake as reaching for your own widgets.
    $html = view('a11y-gate::utilities.gate')->render();

    expect($html)->toContain('<ui-header');
    expect($html)->toContain('<ui-card-panel');
});

it('carries an icon that exists in the control panel icon set', function () {
    // Named icons are looked up in Statamic's own set, and a name that is not
    // there renders as nothing at all: the utility sat in the Tools list with a
    // blank space where every other entry had a mark.
    $utility = collect(Statamic\Facades\Utility::all())
        ->first(fn ($u) => $u->handle() === 'a11y-gate');

    expect($utility)->not->toBeNull();
    expect($utility->icon())->toContain('<svg');
});

it('names the checks it cannot do, including contrast', function () {
    // The gap a buyer is most likely to assume is covered, because every other
    // accessibility tool checks it. Saying so here is cheaper than saying it in
    // a support thread after the sale.
    $body = guideText($this->actingAs($this->user)
        ->get(cp_route('utilities.index').'/a11y-gate')
        ->getContent());

    expect($body)->toContain('Colour contrast');
    expect($body)->toContain('captions');
});
