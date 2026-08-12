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
            'reference' => $entry->reference(),
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
            'reference' => $entry->reference(),
            'values' => ['body' => '<p>The footbridge over the weir.</p>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);
    expect($response->json('refuses'))->toBeFalse();

    // Nothing explaining the tool comes back with a result. That account was a
    // paragraph under every result, then a link under every result, and it is a
    // page under Tools now. An author pressing a button is asking about their
    // page.
    expect($response->json('guide_url'))->toBeNull();
});

it('sends coverage with a clean result, which is where it matters most', function () {
    // A clean page is the case where "how much of this was looked at" carries
    // the most weight, so the panel gets the same coverage on a page with no
    // findings as on a page full of them.
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'reference' => $entry->reference(),
            'values' => ['body' => '<p>The footbridge over the weir.</p>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);

    // Nothing for the author: this page has no video, so there is no gap in
    // their own content to tell them about.
    expect($response->json('notices'))->toBe([]);

    // Everything for whoever audits the addon, still sent, still not drawn.
    expect($response->json('coverage_summary'))->toContain('checks ran in full');
    expect($response->json('coverage'))->not->toBe([]);

    // Every entry that did not run in full has to say why, or the list is a
    // count with nothing behind it.
    foreach ($response->json('coverage') as $check) {
        if ($check['extent'] !== 'full') {
            expect($check['limit'])->not->toBe('');
        }
    }
});

it('tells the author about a video whose captions it could not check', function () {
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'reference' => $entry->reference(),
            'values' => ['body' => '<iframe title="The weir in flood" src="/v"></iframe>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);
    expect($response->json('notices.0'))->toContain('Only you can confirm');
});

it('separates warnings from errors, because only one of them stops a publish', function () {
    $entry = savedPage('<p>The footbridge.</p>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'reference' => $entry->reference(),
            'values' => ['body' => '<a href="#">Read the report</a>'],
        ]);

    $response->assertOk();

    expect($response->json('errors'))->toBe([]);
    expect($response->json('warnings'))->toHaveCount(1);
    expect($response->json('refuses'))->toBeFalse();
});

it('checks a draft, which is the state an author most wants checked', function () {
    // Found by watching the panel say "could not check" on a real draft.
    // `DataResponse::handleDraft()` throws a 404 for an unpublished entry unless
    // the request carries a Live Preview token, so the panel was useless in the
    // one place it is worth most: before the page goes live.
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>The weir</h1>{{ body }}</body></html>');

    $entry = Entry::make()
        ->collection('pages')
        ->slug('weir')
        ->published(false)
        ->data(['title' => 'The weir', 'body' => '<p>The footbridge.</p>']);

    $entry->save();

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'reference' => $entry->reference(),
            'values' => ['body' => '<img src="/a.jpg">'],
        ]);

    $response->assertOk();

    expect($response->json('outcome'))->toBe('checked');
    expect($response->json('errors.0.cta'))->toBe('Add a description');

    // The flag is flipped on the in-memory instance to get a page out of a
    // draft, and it has to go back. An entry left published by a check would be
    // published by the next save that touched it, without anybody asking.
    expect($entry->published())->toBeFalse();

    $onDisk = Entry::query()->where('collection', 'pages')->where('slug', 'weir')->first();
    expect($onDisk->published())->toBeFalse();
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
        ->postJson(cp_route('a11y-gate.check'), ['reference' => $entry->reference()])
        ->assertForbidden();
});

it('404s on an entry that does not exist', function () {
    $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), ['reference' => 'entry::nope'])
        ->assertNotFound();
});
