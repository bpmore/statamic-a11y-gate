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
        expect($lines[0])->toContain('This entry was not saved');
        expect(implode(' ', $lines))->toContain('Add a description');
        // Never the rule id, which means nothing to the person who has to fix it.
        expect(implode(' ', $lines))->not->toContain('image-missing-alt');
    }
});

it('tells the author how much of the page was actually checked', function () {
    // A refusal that listed problems and stopped would invite the reading "and
    // nothing else is wrong". Four of the seven families are blind or
    // half-blind without markup a Statamic site does not produce, so the count
    // goes in the refusal itself rather than somewhere an author might not look.
    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    try {
        $entry->save();
        $this->fail('the gate allowed a page with a missing image description');
    } catch (ValidationException $e) {
        expect(end($e->errors()['a11y_gate']))->toContain('could not run here');
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
    config()->set('a11y-gate.mode', 'warn');

    Log::shouldReceive('warning')->once();

    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect($entry->save())->toBeTrue();
});

it('leaves a collection the site did not ask it to gate alone', function () {
    config()->set('a11y-gate.collections', ['articles']);

    $entry = gatePage('<html lang="en"><body><h1>The weir</h1><img src="/a.jpg"></body></html>');

    expect($entry->save())->toBeTrue();
});
