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

it('puts the panel in a gated collection without being asked', function () {
    // The default, and it was the opposite until somebody pointed out what that
    // meant. The gate refuses a publish on every collection from the moment the
    // addon is installed. Findings come back with the refusal and the panel is
    // the only thing that draws them, so an install without it stops an author
    // and gives them nowhere to look: Statamic's own "The given data was
    // invalid" in the corner and nothing else.
    //
    // Rearranging somebody's publish form uninvited is a real cost. It is not
    // the larger one.
    $field = Collection::find('pages')->entryBlueprint()->field('a11y_panel');

    expect($field)->not->toBeNull();
    expect($field->type())->toBe('accessibility_panel');
});

it('leaves the form alone for a site that switches the panel off', function () {
    // Still supported, and still reasonable for a site that places the field by
    // hand. What it must not do is fail quietly, which is why the config comment
    // says plainly that a refusal is unreadable with no field anywhere.
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', false);

    expect(Collection::find('pages')->entryBlueprint()->field('a11y_panel'))->toBeNull();
});

it('tells the panel which collection and form it is sitting in', function () {
    // Not decoration, and not something the panel can work out for itself.
    // Statamic's create screen sends no entry reference and never sets the
    // blueprint's parent, so this stamp is the only thing that lets a page be
    // checked before its first save. Without it an author on a new page is told
    // to save first, and on a collection that publishes by default the gate
    // then refuses the save.
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', true);

    $blueprint = Collection::find('pages')->entryBlueprint();
    $field = $blueprint->field('a11y_panel');

    expect($field->get('collection'))->toBe('pages');
    expect($field->get('blueprint'))->toBe($blueprint->handle());

    // The stamp is worth nothing unless it reaches the browser. Field config
    // does, through `toPublishArray()`, and that is what the Vue component reads
    // as its `config` prop.
    $published = $field->toPublishArray();

    expect($published['collection'])->toBe('pages');
    expect($published['blueprint'])->toBe($blueprint->handle());
});

it('leaves a collection with no pages alone', function () {
    // No route means no page, so the panel would sit in the form forever saying
    // there is nothing to check.
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', true);

    expect(Collection::find('data')->entryBlueprint()->field('a11y_panel'))->toBeNull();
});

it('leaves a collection the site did not ask it to gate alone', function () {
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', true);
    config()->set('statamic-a11y-gate.collections', ['articles']);

    expect(Collection::find('pages')->entryBlueprint()->field('a11y_panel'))->toBeNull();
});

it('adds the panel whether or not an entry came with the blueprint', function () {
    // Statamic dispatches this event from two places: from the entry when there
    // is one, and from the collection when there is not. The second is the path
    // taken when somebody clicks Create, which is where an author first meets
    // the form, so reading the collection off `$event->entry` would have passed
    // every test here and left the panel missing at exactly that moment.
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', true);

    $withoutEntry = Collection::find('pages')->entryBlueprint();
    expect($withoutEntry->field('a11y_panel'))->not->toBeNull();

    $entry = Statamic\Facades\Entry::make()->collection('pages')->slug('weir');
    expect($entry->blueprint()->field('a11y_panel'))->not->toBeNull();
});
