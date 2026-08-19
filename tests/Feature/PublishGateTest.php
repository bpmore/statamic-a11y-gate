<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

/**
 * The gate, against a booted Statamic and a real save.
 *
 * Every assertion here is about behaviour that cannot be checked without one:
 * that a save is actually stopped, that a draft is left alone, and that the
 * refusal carries the reason. The rules themselves are tested against the corpus
 * with no framework at all.
 */
beforeEach(function () {
    $this->withStandardFakeViews();

    Collection::make('pages')->routes('/{slug}')->save();
});

function gatePage(string $body, bool $published = true): \Statamic\Contracts\Entries\Entry
{
    test()->viewShouldReturnRaw('default', $body);

    return Entry::make()
        ->collection('pages')
        ->slug('weir')
        ->published($published)
        ->data(['title' => 'The weir']);
}

it('refuses a save when the rendered page has an error', function () {
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect(fn () => $entry->save())->toThrow(ValidationException::class);

    // The refusal has to be the whole story: an entry that was refused and
    // written anyway is worse than no gate, because the interface would say it
    // failed while the site served the page.
    expect(Entry::query()->where('collection', 'pages')->where('slug', 'weir')->first())->toBeNull();
});

it('says what is wrong and what to do about it', function () {
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    try {
        $entry->save();
        $this->fail('the gate allowed a page with a missing image description');
    } catch (ValidationException $e) {
        $lines = $e->errors()['a11y_gate'];

        expect($e->status)->toBe(422);

        // The whole sentence, not a fragment of it. The verdict and the count
        // are assembled from two pieces now, because only the caller knows
        // whether the entry was actually refused, and this is what would catch
        // that assembly going wrong.
        expect($lines[0])->toBe('This entry was not saved. One accessibility problem has to be fixed first.');
        expect(implode(' ', $lines))->toContain('Add a description');
        // Never the rule id, which means nothing to the person who has to fix it.
        expect(implode(' ', $lines))->not->toContain('image-missing-alt');
    }
});

it('tells the author about a gap in their own content, and nothing else', function () {
    // A refusal names what the author can act on. The count of which checks ran
    // is an auditor's number and was in this message until somebody read it as
    // an author and asked what they were supposed to do with it.
    $entry = gatePage(
        '<html lang="en"><body><h1>The weir</h1><img src="/a.jpg">'
        .'<iframe title="The weir in flood" src="/v"></iframe></body></html>'
    );

    try {
        $entry->save();
        $this->fail('the gate allowed a page with a missing image description');
    } catch (ValidationException $e) {
        $lines = implode(' ', $e->errors()['a11y_gate']);

        expect($lines)->toContain('Only you can confirm');
        expect(str_contains($lines, 'checks ran in full'))
            ->toBeFalse('the refusal must not count checks at an author');
    }
});

it('says nothing about coverage when there is nothing the author could do', function () {
    // A page with no video has no captions to confirm, so a refusal about a
    // missing image description says exactly that and stops.
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    try {
        $entry->save();
        $this->fail('the gate allowed a page with a missing image description');
    } catch (ValidationException $e) {
        $lines = implode(' ', $e->errors()['a11y_gate']);

        expect($lines)->toContain('Add a description');
        expect(str_contains($lines, 'Only you can confirm'))
            ->toBeFalse('a page with no video must not be told about captions');
    }
});

it('lets a clean page through', function () {
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><p>The footbridge.</p></body></html>');

    expect($entry->save())->toBeTrue();
    expect(Entry::query()->where('collection', 'pages')->where('slug', 'weir')->first())->not->toBeNull();
});

it('checks the values being saved, not the ones already on disk', function () {
    // The claim the whole product rests on: the gate judges the version being
    // saved, not the one already live. The template prints a field, so a stale
    // render shows the old value and a live one shows the new. A fixture whose
    // template ignores the changed field cannot tell the two apart, and that is
    // exactly the trap that hid the render spike's result for three attempts.
    //
    // **What this does NOT prove, checked by mutation:** deleting
    // `substitute()` from the renderer leaves this test green, and leaves a real
    // Statamic 6 site answering correctly too, which was tried afterwards. The
    // repository hands back the same in-memory instance either way. That line is
    // kept as insurance against a repository that would not, and the renderer
    // says plainly that it is unproven rather than implying this test covers it.
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>The weir</h1>{{ body }}</body></html>');

    $entry = Entry::make()
        ->collection('pages')
        ->slug('weir')
        ->published(true)
        ->data(['title' => 'The weir', 'body' => '<p>The footbridge.</p>']);

    expect($entry->save())->toBeTrue();

    // On disk this page is clean. In hand it is not.
    $entry->set('body', '<img src="/a.jpg">');

    expect(fn () => $entry->save())->toThrow(ValidationException::class);
});

it('lets a page through when the only findings are warnings', function () {
    // The boundary the product is defined by: errors refuse a publish and
    // warnings do not. A gate that blocked on every finding would be switched
    // off in a week, and one that blocked on nothing would not be a gate.
    //
    // A link to "#" goes nowhere, which is a house rule and a warning: no
    // success criterion covers it, and an author part-way through a page may
    // legitimately have a placeholder in it.
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><a href="#">Read the report</a></body></html>');

    expect($entry->save())->toBeTrue();
});

it('does not check a draft', function () {
    // A draft is not a published page, and refusing to save one would stop an
    // author part-way through their work.
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>', published: false);

    expect($entry->save())->toBeTrue();
});

it('refuses a page that could not be rendered, rather than passing it', function () {
    $entry = gatePage('{{ throw_something_undefined_that_explodes }}');

    // Deliberately not a specific message: the point is that a check which could
    // not run stops the save. Passing here would publish on the strength of a
    // check nobody ran.
    try {
        $entry->save();
        $this->fail('the gate allowed an entry whose page did not render');
    } catch (ValidationException $e) {
        expect(implode(' ', $e->errors()['a11y_gate']))->toContain('could not run');
    }
});

it('warns instead of refusing when the site asks it to', function () {
    config()->set('statamic-a11y-gate.mode', 'warn');

    Log::shouldReceive('warning')->once();

    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect($entry->save())->toBeTrue();
});

it('does not tell the log an entry was not saved when it was', function () {
    // The test above asserts only that a warning was logged, never what it
    // said, which is why this shipped: warn mode's line read "would have
    // refused this entry: This entry was not saved." about an entry saved on
    // the next line of execution. It contradicted its own first clause, and in
    // warn mode that line is the addon's entire output.
    config()->set('statamic-a11y-gate.mode', 'warn');

    $logged = '';

    Log::shouldReceive('warning')->once()->withArgs(function ($message, $context) use (&$logged) {
        $logged = $message;

        return true;
    });

    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect($entry->save())->toBeTrue();

    // str_contains and a boolean, not `->not->toContain($needle, $message)`,
    // which takes a list of needles and would pass vacuously.
    expect(str_contains($logged, 'was not saved'))
        ->toBeFalse("warn mode saves the entry, so the log must not say otherwise: [{$logged}]");

    // And it still has to say what it found, or the mode is indistinguishable
    // from the addon not being installed.
    expect(str_contains($logged, 'accessibility problem'))
        ->toBeTrue("warn mode must still name what it found: [{$logged}]");
});

it('leaves a collection the site did not ask it to gate alone', function () {
    config()->set('statamic-a11y-gate.collections', ['articles']);

    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect($entry->save())->toBeTrue();
});

it('says a page has no address yet rather than pretending it has no page', function () {
    // Found by installing the addon on a stock Statamic site, not by anything in
    // this file. The default `pages` collection routes on `{parent_uri}/{slug}`,
    // and that URI comes from the page tree. An entry being created is not in the
    // tree while `EntrySaving` runs, so it has no URL and there is nothing to
    // fetch.
    //
    // The gate let it through, which is the right answer: refusing would mean no
    // page could ever be created in such a collection. What it said was wrong.
    // "The entry has no page of its own" is true of a collection the site never
    // routes, and false here.
    $this->withStandardFakeViews();

    Collection::make('structured')->routes('{parent_uri}/{slug}')->structure(new \Statamic\Structures\CollectionStructure)->save();

    $entry = Entry::make()->collection('structured')->slug('weir')->published(true)
        ->data(['title' => 'The weir']);

    expect($entry->absoluteUrl())->toBeNull();

    $result = app(\Bpmore\A11yGate\Gate\PublishGate::class)->inspect($entry);

    expect($result->outcome)->toBe('not-applicable');
    expect($result->shouldRefuse())->toBeFalse();
    expect($result->reason)->toContain('no address yet');
    expect($result->reason)->toContain('next save');
});

it('still says a routeless collection has no page of its own', function () {
    // The other half, kept separate so a fix to one cannot silently answer for
    // the other. This collection has no route at all: there is no page, and there
    // never will be, however many times it is saved.
    $this->withStandardFakeViews();

    Collection::make('data')->save();

    $entry = Entry::make()->collection('data')->slug('rows')->published(true)
        ->data(['title' => 'Rows']);

    $result = app(\Bpmore\A11yGate\Gate\PublishGate::class)->inspect($entry);

    expect($result->outcome)->toBe('not-applicable');
    expect($result->reason)->toBe('the entry has no page of its own');
});
