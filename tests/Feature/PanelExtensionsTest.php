<?php

declare(strict_types=1);

use Bpmore\A11yGate\Panel\PanelExtensions;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Fields\Field;

/**
 * The seam another addon uses to speak in the panel, without the gate's own
 * rules changing by a word.
 */
beforeEach(function () {
    Collection::make('pages')->routes('/{slug}')->save();
    $this->entry = Entry::make()->collection('pages')->slug('one')->data(['title' => 'One']);
    $this->entry->saveQuietly();
});

function panelPreload($entry = null): array
{
    $field = (new Field('accessibility_panel', ['type' => 'accessibility_panel']));

    if ($entry !== null) {
        $field->setParent($entry);
    }

    return $field->fieldtype()->preload();
}

it('hands the component nothing when nobody registered anything', function () {
    expect(panelPreload($this->entry))->toBe(['extensions' => []]);
});

it('hands the component what a provider says about this page, normalised', function () {
    PanelExtensions::register(fn ($entry) => [
        'heading' => 'From the last scan: '.$entry->slug(),
        'lines' => ['Two images have no description.', '', 42],
        'link' => ['url' => '/cp/somewhere', 'text' => 'Open the queue'],
    ]);

    $blocks = panelPreload($this->entry)['extensions'];

    expect($blocks)->toHaveCount(1);
    expect($blocks[0])->toBe([
        'heading' => 'From the last scan: one',
        'lines' => ['Two images have no description.', '42'],
        'link' => ['url' => '/cp/somewhere', 'text' => 'Open the queue'],
        'tone' => 'default',
    ]);
});

it('leaves out a provider with nothing to say, and keeps the order of the rest', function () {
    PanelExtensions::register(fn () => ['heading' => 'First']);
    PanelExtensions::register(fn () => null);
    PanelExtensions::register(fn () => ['heading' => 'Third', 'tone' => 'warning']);

    $blocks = panelPreload($this->entry)['extensions'];

    expect(array_column($blocks, 'heading'))->toBe(['First', 'Third']);
    expect($blocks[0]['link'])->toBeNull();
    expect($blocks[1]['tone'])->toBe('warning');
});

it('draws a provider that throws as a block saying so, rather than going quiet', function () {
    // A panel that went silent about a broken companion would look like a
    // page with nothing else to say.
    PanelExtensions::register(function () {
        throw new RuntimeException('the scan database is not there');
    });

    $blocks = panelPreload($this->entry)['extensions'];

    expect($blocks)->toHaveCount(1);
    expect($blocks[0]['tone'])->toBe('warning');
    expect($blocks[0]['lines'])->toBe(['the scan database is not there']);
});

it('says nothing on the create screen, where there is no entry to speak about', function () {
    PanelExtensions::register(fn () => ['heading' => 'Would be wrong here']);

    expect(panelPreload(null))->toBe(['extensions' => []]);
});

it('is drawn by the panel', function () {
    // The script is the half an author sees. This does not run it; it asserts
    // the seam is wired to it, so a block cannot be produced and never shown.
    $src = file_get_contents(__DIR__.'/../../resources/js/a11y-panel.js');

    expect(str_contains($src, "props: ['config', 'meta']"))->toBeTrue('the component accepts meta');
    expect(str_contains($src, 'meta?.extensions'))->toBeTrue('the template draws the extensions');
    expect(str_contains($src, 'ext.link.url'))->toBeTrue('the template draws a block\'s link');
});
