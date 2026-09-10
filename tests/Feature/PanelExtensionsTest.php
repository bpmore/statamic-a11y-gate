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
        'mark' => null,
        'refusalKey' => null,
    ]);
});

it('carries a mark a provider hands it, so a companion can be recognised', function () {
    PanelExtensions::register(fn () => [
        'heading' => 'From the last scan',
        'mark' => ['url' => '/cp/somewhere/mark?site=default', 'alt' => '  Example Trust  '],
    ]);

    expect(panelPreload($this->entry)['extensions'][0]['mark'])
        ->toBe(['url' => '/cp/somewhere/mark?site=default', 'alt' => 'Example Trust']);
});

it('leaves out a mark nobody could have described to them', function () {
    // An image with no text alternative, in the panel of an addon whose whole
    // job is to refuse exactly that on the pages it checks.
    foreach ([['url' => '/cp/mark'], ['url' => '/cp/mark', 'alt' => ''], ['url' => '/cp/mark', 'alt' => '   ']] as $mark) {
        PanelExtensions::flush();
        PanelExtensions::register(fn () => ['heading' => 'A block', 'mark' => $mark]);

        expect(panelPreload($this->entry)['extensions'][0]['mark'])
            ->toBeNull('a mark with no words was drawn: '.json_encode($mark));
    }
});

it('leaves out a mark whose address is not one the browser should be handed', function () {
    $refused = [
        'javascript:alert(1)',
        'JavaScript:alert(1)',
        'vbscript:msgbox',
        'data:text/html,<script>alert(1)</script>',
        'mark.png',
        '',
    ];

    foreach ($refused as $url) {
        PanelExtensions::flush();
        PanelExtensions::register(fn () => ['heading' => 'A block', 'mark' => ['url' => $url, 'alt' => 'Example Trust']]);

        expect(panelPreload($this->entry)['extensions'][0]['mark'])
            ->toBeNull("a mark addressed [{$url}] was drawn");
    }

    foreach (['/cp/mark', 'https://example.test/mark.svg', 'http://example.test/mark.svg', 'data:image/svg+xml;base64,PHN2Zz48L3N2Zz4='] as $url) {
        PanelExtensions::flush();
        PanelExtensions::register(fn () => ['heading' => 'A block', 'mark' => ['url' => $url, 'alt' => 'Example Trust']]);

        expect(panelPreload($this->entry)['extensions'][0]['mark'])
            ->toBe(['url' => $url, 'alt' => 'Example Trust'], "a mark addressed [{$url}] was refused");
    }
});

it('gives a block that could not answer no mark to wear', function () {
    // A warning is the gate reporting a problem, in the control panel's own
    // colours. Nobody puts their name against one.
    PanelExtensions::register(function () {
        throw new RuntimeException('the scan database is not there');
    });

    expect(panelPreload($this->entry)['extensions'][0]['mark'])->toBeNull();
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

it('carries a block\'s own refusal key, so its refusal is not keyed to somebody\'s title', function () {
    // Statamic drops a validation error that is not keyed to a blueprint
    // field, so an addon refusing a save has to pick a field, and the refusal
    // then reads as a fault in that field. A11y Docs was keying "this entry
    // links to a document nobody can read" to `title`.
    PanelExtensions::register(fn () => ['heading' => 'Documents', 'refusalKey' => 'a11y_docs']);

    expect(panelPreload($this->entry)['extensions'][0]['refusalKey'])->toBe('a11y_docs');
});

it('takes a refusal key only in the shape of a handle', function () {
    // It is read as a property name off the errors object in the browser.
    foreach ([
        ['a11y_docs', 'a11y_docs'],
        ['x', 'x'],
        ['A11yDocs', null],
        ['a11y-docs', null],
        ['9lives', null],
        ['__proto__', null],
        ['', null],
        [['not' => 'a string'], null],
        [null, null],
    ] as [$given, $expected]) {
        PanelExtensions::flush();
        PanelExtensions::register(fn () => ['heading' => 'Documents', 'refusalKey' => $given]);

        expect(panelPreload($this->entry)['extensions'][0]['refusalKey'])
            ->toBe($expected, 'refusalKey '.json_encode($given));
    }
});

it('gives every block a refusal key, null when it named none', function () {
    // The browser reads the property whether or not a provider set it, and a
    // key that is sometimes absent is a second shape to keep in step.
    PanelExtensions::register(fn () => ['heading' => 'Documents']);

    expect(panelPreload($this->entry)['extensions'][0])->toHaveKey('refusalKey')
        ->and(panelPreload($this->entry)['extensions'][0]['refusalKey'])->toBeNull();
});

it('answers what a provider may rely on it drawing', function () {
    // A provider ships separately. Naming a refusalKey at a gate too old to
    // read one sends the refusal to a key nothing draws, and nothing fails:
    // the save is still refused and the author is never told why. This is how
    // a provider tells the two versions apart.
    expect(PanelExtensions::supports('refusalKey'))->toBeTrue()
        ->and(PanelExtensions::supports('somethingNobodyHasBuilt'))->toBeFalse();
});

it('is drawn by the panel', function () {
    // The script is the half an author sees. This does not run it; it asserts
    // the seam is wired to it, so a block cannot be produced and never shown.
    $src = file_get_contents(__DIR__.'/../../resources/js/a11y-panel.js');

    expect(str_contains($src, "props: ['config', 'meta']"))->toBeTrue('the component accepts meta');
    expect(str_contains($src, 'meta?.extensions'))->toBeTrue('the template draws the extensions');
    expect(str_contains($src, 'ext.link.url'))->toBeTrue('the template draws a block\'s link');
    expect(str_contains($src, ':src="ext.mark.url"'))->toBeTrue('the template draws a block\'s mark');
    expect(str_contains($src, ':alt="ext.mark.alt"'))->toBeTrue('the mark is drawn with its words');
    expect(str_contains($src, 'refusalFor(ext)'))->toBeTrue('the template draws a block\'s own refusal');
});
