<?php

declare(strict_types=1);

use Bpmore\A11yGate\Gate\GateSettings;
use Statamic\Contracts\Addons\SettingsRepository;
use Statamic\Facades\Addon;

/**
 * The settings screen, which exists because of who ends up holding a site.
 *
 * A developer installs this from the marketplace and hands over. The person left
 * with it opens the control panel, wants the gate to stop refusing while they
 * clear a backlog, and cannot edit a PHP file or clear a cache. Everything here
 * is about that handover working without them.
 */
// Cleared in TestCase for every test in the suite, not just this file: a saved
// settings file is shared state, and the first test to write one would decide
// the answer for every test that ran after it.

it('offers a settings screen at all', function () {
    $addon = Addon::get('bpmore/statamic-a11y-gate');

    expect($addon)->not->toBeNull();
    expect($addon->hasSettingsBlueprint())->toBeTrue();
    expect($addon->settingsUrl())->toContain('addons');
});

it('asks in plain words, with no handles or file paths', function () {
    // The screen is for somebody who was handed a site, so a field labelled
    // "opt_in_checks" or an instruction naming config/a11y-gate.php would be
    // the same failure as the panel telling an author how many checks ran.
    $blueprint = Addon::get('bpmore/statamic-a11y-gate')->settingsBlueprint();

    $text = collect($blueprint->fields()->all())
        ->flatMap(fn ($field) => [$field->display(), $field->instructions()])
        ->filter()
        ->implode(' ');

    expect($text)->toContain('When a page has a problem');

    foreach (['config/', 'blueprint', 'handle', '.env', 'yaml'] as $jargon) {
        expect(str_contains(strtolower($text), strtolower($jargon)))
            ->toBeFalse("the settings screen must not say '{$jargon}' to somebody who was handed a site");
    }
});

it('keeps the panel toggle beside the list it refers to', function () {
    // "Add the panel to the collections above" is only true while it is below
    // them. In its own card further down the page it was read as every
    // collection on the site, which is the opposite of what it does.
    $blueprint = Addon::get('bpmore/statamic-a11y-gate')->settingsBlueprint();

    $handlesBySection = collect($blueprint->tabs()->all())
        ->flatMap(fn ($tab) => $tab->sections()->all())
        ->map(fn ($section) => $section->fields()->all()->keys()->all());

    $together = $handlesBySection->first(
        fn ($handles) => in_array('collections', $handles, true)
            && in_array('add_panel_to_blueprints', $handles, true)
    );

    expect($together)->not->toBeNull();
    expect(array_search('add_panel_to_blueprints', $together, true))
        ->toBeGreaterThan(array_search('collections', $together, true));
});

it('leaves the config file in charge until somebody saves the screen', function () {
    // The trap this was built around. An unsaved settings record is not empty:
    // it comes back carrying the blueprint's own defaults. Reading its values
    // alone would let a field default silently beat a config file a developer
    // wrote on purpose, and the gate would quietly ignore its own configuration.
    config()->set('statamic-a11y-gate.mode', 'warn');

    expect(app(GateSettings::class)->refuses())->toBeFalse();
});

it('lets the screen win once it has been saved', function () {
    config()->set('statamic-a11y-gate.mode', 'warn');

    $addon = Addon::get('bpmore/statamic-a11y-gate');
    $settings = $addon->settings();
    $settings->set('mode', 'refuse');
    app(SettingsRepository::class)->save($settings);

    // Resolved fresh, because the real request that reads this is a different
    // one from the request that saved it.
    app()->forgetInstance(GateSettings::class);

    expect(app(GateSettings::class)->refuses())->toBeTrue();
});

it('treats a switched-off toggle as an answer rather than as absent', function () {
    // "Do not add the panel" is chosen by switching something off. Skipping
    // falsey values would make it unchoosable and the screen would appear to
    // ignore its own control.
    //
    // Reading `raw()` rather than `all()` is what makes this work: `all()`
    // blends the blueprint's defaults over what was saved, so a toggle somebody
    // switched off comes back as an empty string and a mode nobody chose comes
    // back as "refuse".
    config()->set('statamic-a11y-gate.add_panel_to_blueprints', true);

    $addon = Addon::get('bpmore/statamic-a11y-gate');
    $settings = $addon->settings();
    $settings->set('add_panel_to_blueprints', false);
    app(SettingsRepository::class)->save($settings);

    app()->forgetInstance(GateSettings::class);

    expect(app(GateSettings::class)->addsPanel)->toBeFalse();
});

it('does not let an untouched field on the screen overrule the config file', function () {
    // Somebody opens the screen, flips one toggle, saves. Everything else on
    // that form still has the blueprint's own defaults in it, and reading the
    // blended values would quietly turn a site configured to report into a site
    // that refuses, because "refuse" is the field's default.
    //
    // Found by mutation: swapping `raw()` for `all()` passed every other test
    // here, because in each of them the default and the saved value agreed.
    config()->set('statamic-a11y-gate.mode', 'warn');

    $addon = Addon::get('bpmore/statamic-a11y-gate');
    $settings = $addon->settings();
    $settings->set('add_panel_to_blueprints', true);
    app(SettingsRepository::class)->save($settings);

    app()->forgetInstance(GateSettings::class);

    $resolved = app(GateSettings::class);

    expect($resolved->addsPanel)->toBeTrue();
    expect($resolved->refuses())->toBeFalse();
});

it('falls back to the config file for a field left empty', function () {
    // Statamic drops empty values when it writes the settings file, so an empty
    // field is stored as absent rather than as a choice. That is its behaviour
    // and not something to work around: the config file answers, and the config
    // file's own default for collections is "check everything".
    config()->set('statamic-a11y-gate.collections', ['blog']);

    $addon = Addon::get('bpmore/statamic-a11y-gate');
    $settings = $addon->settings();
    $settings->set('collections', []);
    $settings->set('mode', 'warn');
    app(SettingsRepository::class)->save($settings);

    app()->forgetInstance(GateSettings::class);

    $resolved = app(GateSettings::class);

    expect($resolved->refuses())->toBeFalse();
    expect($resolved->gates('blog'))->toBeTrue();
    expect($resolved->gates('pages'))->toBeFalse();
});
