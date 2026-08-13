<?php

declare(strict_types=1);

use Statamic\Facades\Collection;

/**
 * Putting the panel into blueprints so nobody edits seven files by hand.
 *
 * The whole feature is one listener and a flag, and almost every assertion here
 * is about when it should keep its hands off: a site that did not ask, a
 * collection that is not gated, a collection with no pages, and a blueprint
 * where somebody already placed the field themselves.
 */
beforeEach(function () {
    Collection::make('pages')->routes('/{slug}')->save();
    Collection::make('data')->save();
});

it('does nothing at all unless the site asks for it', function () {
    // The default, and the reason it is the default: a publish form that
    // rearranges itself on install is a form somebody did not agree to.
    $blueprint = Collection::find('pages')->entryBlueprint();

    expect($blueprint->field('a11y_panel'))->toBeNull();
});

it('adds the panel to a gated collection that has pages', function () {
    config()->set('a11y-gate.add_panel_to_blueprints', true);

    $field = Collection::find('pages')->entryBlueprint()->field('a11y_panel');

    expect($field)->not->toBeNull();
    expect($field->type())->toBe('accessibility_panel');
});

it('leaves a collection with no pages alone', function () {
    // No route means no page, so the panel would sit in the form forever saying
    // there is nothing to check.
    config()->set('a11y-gate.add_panel_to_blueprints', true);

    expect(Collection::find('data')->entryBlueprint()->field('a11y_panel'))->toBeNull();
});

it('leaves a collection the site did not ask it to gate alone', function () {
    config()->set('a11y-gate.add_panel_to_blueprints', true);
    config()->set('a11y-gate.collections', ['articles']);

    expect(Collection::find('pages')->entryBlueprint()->field('a11y_panel'))->toBeNull();
});

it('adds the panel whether or not an entry came with the blueprint', function () {
    // Statamic dispatches this event from two places: from the entry when there
    // is one, and from the collection when there is not. The second is the path
    // taken when somebody clicks Create, which is where an author first meets
    // the form, so reading the collection off `$event->entry` would have passed
    // every test here and left the panel missing at exactly that moment.
    config()->set('a11y-gate.add_panel_to_blueprints', true);

    $withoutEntry = Collection::find('pages')->entryBlueprint();
    expect($withoutEntry->field('a11y_panel'))->not->toBeNull();

    $entry = Statamic\Facades\Entry::make()->collection('pages')->slug('weir');
    expect($entry->blueprint()->field('a11y_panel'))->not->toBeNull();
});
