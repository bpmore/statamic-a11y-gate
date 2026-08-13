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

it('checks a page before it has ever been saved', function () {
    // The case this endpoint was rebuilt for. Statamic's create screen sends no
    // entry reference, and on a collection that publishes by default there is no
    // way to save without publishing, and the gate refuses the publish. So an
    // author with a broken page was told to save it first and then stopped from
    // saving it: told to do the one thing the gate would not allow.
    //
    // The collection and blueprint come from the panel's own field config,
    // stamped there by the listener that places the field.
    $this->viewShouldReturnRaw('default', '<html lang="en"><body><h1>The weir</h1>{{ body }}</body></html>');

    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'collection' => 'pages',
            'values' => ['title' => 'The weir', 'body' => '<img src="/a.jpg">'],
        ]);

    $response->assertOk();

    expect($response->json('outcome'))->toBe('checked');
    expect($response->json('errors.0.cta'))->toBe('Add a description');

    // Nothing was written. An endpoint that checks a page must not be a way to
    // create one, and the whole point is that this entry does not exist yet.
    expect(Entry::query()->where('collection', 'pages')->count())->toBe(0);
});

it('asks for a title rather than checking somebody else page', function () {
    // A URL is built from the slug and the slug from the title, so an unnamed
    // page has no URL. Letting it through does not produce "no page": it
    // produces the collection's route with a hole in it, which renders whatever
    // lives at that address and reports back about a page the author never
    // opened. Found by asserting the harmless outcome and getting a rendered
    // one instead.
    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'collection' => 'pages',
            'values' => ['body' => '<img src="/a.jpg">'],
        ]);

    $response->assertStatus(422);

    expect($response->json('message'))->toContain('no title yet');
});

it('will not check a new page for somebody who cannot create one', function () {
    // This renders a page from values the caller supplies. There is no entry to
    // check permission against yet, so the collection is what stands in for it.
    $this->setTestRoles(['no_entries' => ['access cp']]);

    $nobody = User::make()->assignRole('no_entries');
    $nobody->save();

    $this->actingAs($nobody)
        ->postJson(cp_route('a11y-gate.check'), [
            'collection' => 'pages',
            'values' => ['title' => 'The weir'],
        ])
        ->assertForbidden();
});

it('says so when the collection it was given does not exist', function () {
    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'collection' => 'nope',
            'values' => ['title' => 'The weir'],
        ]);

    $response->assertNotFound();

    expect($response->json('message'))->toContain('[nope]');
});

it('says why when the entry has never been saved', function () {
    // Statamic's create screen carries no `reference` in its view data, only its
    // edit screen does, so the panel has nothing to send until the first save.
    // That is not an edge: it is every check pressed on a new page.
    //
    // This used to be a bare `abort(404)`, which Laravel answers with
    // `{"message": ""}`. The panel then drew nothing at all, so pressing Check on
    // a new page looked identical to pressing it on a page with nothing wrong.
    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), [
            'values' => ['title' => 'Not saved yet', 'body' => '<img src="/a.jpg">'],
        ]);

    $response->assertStatus(422);

    expect($response->json('message'))->toContain('has not been saved');
});

it('never answers a failure with an empty message', function () {
    // The invariant, held across every way this endpoint can refuse to answer. An
    // empty message is what let a failed check look like a clean one, and no
    // single test above would have caught it returning to any one of these.
    $entry = savedPage('<p>The footbridge.</p>');

    $this->setTestRoles(['no_entries' => ['access cp']]);
    $nobody = User::make()->assignRole('no_entries');
    $nobody->save();

    $failures = [
        'never saved' => [$this->user, []],
        'entry is gone' => [$this->user, ['reference' => 'entry::nope']],
        'not allowed to view it' => [$nobody, ['reference' => $entry->reference()]],
    ];

    foreach ($failures as $case => [$as, $payload]) {
        $response = $this->actingAs($as)->postJson(cp_route('a11y-gate.check'), $payload);

        expect($response->status())->toBeGreaterThanOrEqual(400, "[{$case}] should have failed");
        expect(trim((string) $response->json('message')) !== '')
            ->toBeTrue("[{$case}] failed with an empty message, which the panel draws as nothing at all");
    }

    // The loop asserts nothing if the list is ever emptied, and this is the whole
    // point of the test.
    expect($failures)->toHaveCount(3);
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

it('404s on an entry that does not exist, and says so', function () {
    $response = $this->actingAs($this->user)
        ->postJson(cp_route('a11y-gate.check'), ['reference' => 'entry::nope']);

    $response->assertNotFound();

    expect($response->json('message'))->toContain('could not be found');
});
