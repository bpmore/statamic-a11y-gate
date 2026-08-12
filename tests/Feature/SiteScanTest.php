<?php

declare(strict_types=1);

use Statamic\Facades\Collection;
use Statamic\Facades\Entry;

/**
 * The whole-site scan.
 *
 * Its one claim beyond what the panel already does is the grouping: the same
 * problem on every page came from a template, and a problem on one page came
 * from whoever wrote that page. Most of this file is about proving that
 * distinction survives contact with real pages.
 */
beforeEach(function () {
    $this->withStandardFakeViews();

    Collection::make('pages')->routes('/{slug}')->save();
});

function scanPage(string $slug, string $body, bool $published = true): void
{
    Entry::make()
        ->collection('pages')
        ->slug($slug)
        ->published($published)
        ->data(['title' => ucfirst($slug), 'body' => $body])
        // Quietly, because the gate is listening and would refuse most of these:
        // every fixture here is a page that is deliberately wrong. Saving them
        // through the gate would test the gate, which is another file's job.
        ->saveQuietly();
}

it('groups a template problem into one line and counts the pages', function () {
    // The footer link is in the layout, so it is on every page. Three findings,
    // one cause, one fix. A report that listed it three times would bury the
    // thing that costs one afternoon under the things that cost three.
    test()->viewShouldReturnRaw(
        'default',
        '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}<a href="/x">Read more</a></body></html>'
    );

    scanPage('one', '<p>Fine.</p>');
    scanPage('two', '<p>Fine.</p>');
    scanPage('three', '<p>Fine.</p>');

    $report = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($report->pagesChecked)->toBe(3);
    expect($report->findings)->toHaveCount(1);
    expect($report->findings[0]->violation->rule)->toBe('link-unclear');
    expect($report->findings[0]->pageCount())->toBe(3);
    expect($report->findings[0]->reach(3))->toBe('every page');
});

it('keeps a problem on one page as a problem on one page', function () {
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('one', '<p>Fine.</p>');
    scanPage('two', '<img src="/only-here.jpg">');
    scanPage('three', '<p>Fine.</p>');

    $report = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($report->findings)->toHaveCount(1);
    expect($report->findings[0]->reach(3))->toBe('1 page');
    expect($report->findings[0]->pages[0])->toContain('two');
});

it('does not merge two different images into one finding', function () {
    // The grouping key is the rule plus the thing that tripped it. Grouping on
    // the rule alone would collapse every undescribed image on the site into one
    // line reading "12 pages", which is true and useless.
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('one', '<img src="/a.jpg">');
    scanPage('two', '<img src="/b.jpg">');

    $report = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($report->findings)->toHaveCount(2);
});

it('puts the most widespread problem first, and errors before warnings', function () {
    // The order is the argument: one template fix worth three pages beats one
    // page's worth of author time, and sorting the other way buries it.
    test()->viewShouldReturnRaw(
        'default',
        '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}<a href="#">Somewhere</a></body></html>'
    );

    scanPage('one', '<img src="/a.jpg">');
    scanPage('two', '<p>Fine.</p>');
    scanPage('three', '<p>Fine.</p>');

    $report = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($report->errors())->toHaveCount(1);
    expect($report->warnings())->toHaveCount(1);
    expect($report->findings[0]->isError())->toBeTrue();
    expect($report->warnings()[0]->pageCount())->toBe(3);
});

it('names the pages it could not read rather than counting them as clean', function () {
    // The fail-closed rule, at site scale. A report that scanned 40 pages, read
    // 36, and said "nothing found" would be claiming coverage it did not have.
    test()->viewShouldReturnRaw('default', '');

    scanPage('one', '<p>Fine.</p>');

    $report = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($report->pagesChecked)->toBe(0);
    expect($report->unreadable)->toHaveCount(1);
    expect($report->shouldFail())->toBeTrue();
});

it('fails on errors and passes on warnings alone', function () {
    // The same boundary the gate enforces. A build that stopped for every
    // warning would be turned off, and one that stopped for nothing is not a
    // gate.
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('one', '<a href="#">Somewhere</a>');

    $warningsOnly = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($warningsOnly->warnings())->toHaveCount(1);
    expect($warningsOnly->shouldFail())->toBeFalse();

    scanPage('two', '<img src="/a.jpg">');

    $withAnError = app(Bpmore\A11yGate\Scan\SiteScanner::class)->scan(
        Entry::query()->where('collection', 'pages')->get()
    );

    expect($withAnError->shouldFail())->toBeTrue();
});

it('runs as a command and exits non-zero when a page has an error', function () {
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('one', '<img src="/a.jpg">');

    $this->artisan('statamic:a11y:check')
        ->expectsOutputToContain('Checked 1 pages.')
        ->expectsOutputToContain('Add a description')
        ->assertExitCode(1);
});

it('exits zero on a clean site and still says what it could not see', function () {
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('one', '<p>The footbridge.</p>');

    $this->artisan('statamic:a11y:check')
        ->expectsOutputToContain('Nothing found.')
        // Never a clean bill of health, even here.
        ->expectsOutputToContain('has not been proven accessible')
        ->assertExitCode(0);
});

it('leaves drafts out unless asked', function () {
    test()->viewShouldReturnRaw('default', '<html lang="en"><body><h1>{{ title }}</h1>{{ body }}</body></html>');

    scanPage('published-one', '<p>Fine.</p>');
    scanPage('draft-one', '<img src="/a.jpg">', published: false);

    $this->artisan('statamic:a11y:check')->assertExitCode(0);
    $this->artisan('statamic:a11y:check', ['--drafts' => true])->assertExitCode(1);
});
