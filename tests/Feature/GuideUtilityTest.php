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

it('is reachable under Tools', function () {
    $this->actingAs($this->user)
        ->get(cp_route('utilities.index').'/a11y-gate')
        ->assertOk();
});

it('refuses to call anything compliant or accessible', function () {
    $response = $this->actingAs($this->user)->get(cp_route('utilities.index').'/a11y-gate');

    // Whitespace collapsed first: the sentence is wrapped across lines in the
    // template, and a raw substring match would fail on the newline rather than
    // on the claim, which is the wrong reason for this test to go red.
    $body = strtolower((string) preg_replace('/\s+/', ' ', $response->getContent()));

    expect(str_contains($body, 'has not been proven accessible'))
        ->toBeTrue('the guide must say plainly that a clean result proves nothing');

    expect(str_contains($body, 'compliant'))
        ->toBeFalse('this addon never uses the word compliant');
});

it('names the checks it cannot do, including contrast', function () {
    // The gap a buyer is most likely to assume is covered, because every other
    // accessibility tool checks it. Saying so here is cheaper than saying it in
    // a support thread after the sale.
    $body = $this->actingAs($this->user)
        ->get(cp_route('utilities.index').'/a11y-gate')
        ->getContent();

    expect($body)->toContain('Colour contrast');
    expect($body)->toContain('captions');
});
