<?php

declare(strict_types=1);

use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\User;

/**
 * The endpoint the panel asks. Its whole reason to exist is the unsaved values,
 * so that is what most of this file is about.
 */
beforeEach(function () {
    $this->withStandardFakeViews();

    Collection::make('pages')->routes('/{slug}')->save();

    $this->user = User::make()->makeSuper();
    $this->user->save();
});

function savedPage(string $body): \Statamic\Contracts\Entries\Entry
{
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>The weir</h1>{{ body }}</body></html>');

    $entry = Entry::make()
        ->collection('pages')
        ->slug('weir')
        ->published(true)
        ->data(['title' => 'The weir', 'body' => $body]);

    $entry->save();

    return $entry;
}

it('checks the values in the form, not the ones on disk', function () {
    // The assertion the panel is worth building for. On disk this page is
    // clean; the author has just pasted an image with no description and has
    // not saved. A panel that answered from the file would show a clean page at
    // the exact moment the author needed to be told otherwise.
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'entry' => $entry->id(),
            'values' => ['body' => '<img src="/a.jpg">'],
        ]);

    $response->assertOk();

    expect($response->json('outcome'))->toBe('checked');
    expect($response->json('refuses'))->toBeTrue();
    expect($response->json('errors.0.message'))->toContain('no description');
    expect($response->json('errors.0.cta'))->toBe('Add a description');
});

it('reports a clean page as clean without claiming it is accessible', function () {
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'entry' => $entry->id(),
            'values' => ['body' => '<p>The footbridge over the weir.</p>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);
    expect($response->json('refuses'))->toBeFalse();

    // The sentence a customer reads comes from the server, so it cannot drift
    // from what this project is willing to defend.
    $limits = $response->json('limits');
    expect($limits)->toContain('has not been proven accessible');
    expect(str_contains(strtolower($limits), 'compliant'))->toBeFalse('the panel must never use the word compliant');
});

it('separates warnings from errors, because only one of them stops a publish', function () {
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'entry' => $entry->id(),
            'values' => ['body' => '<a href="#">Read the report</a>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);
    expect($response->json('warnings'))->toHaveCount(1);
    expect($response->json('refuses'))->toBeFalse();
});

it('refuses to check an entry for somebody who cannot view it', function () {
    // The endpoint renders a page from values the caller supplies, so it must
    // not be a way to read an entry the caller has no business reading.
    //
    // The role has control-panel access and nothing else, on purpose: a user
    // with no access at all is turned away by Statamic's own middleware, and
    // this would then pass without the authorize() call ever running.
    $this->setTestRoles(['no_entries' => ['access cp']]);

    $entry = savedPage('<p>The footbridge.</p>');

    $nobody = User::make()->assignRole('no_entries');
    $nobody->save();

    $this->actingAs($nobody)
        ->postJson(cp_route('a11y-gate.check'), ['entry' => $entry->id()])
        ->assertForbidden();
});

it('404s on an entry that does not exist', function () {
    $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), ['entry' => 'nope'])
        ->assertNotFound();
});
