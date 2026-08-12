<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Commands;

use Bpmore\A11yGate\Scan\ScannedFinding;
use Bpmore\A11yGate\Scan\ScanReport;
use Bpmore\A11yGate\Scan\SiteScanner;
use Illuminate\Console\Command;
use Statamic\Console\RunsInPlease;
use Statamic\Facades\Entry;

/**
 * Check every page the site serves, and say what repeats.
 *
 * A command rather than a screen, for three reasons. The person who wants this
 * is a developer, and they are in a terminal. It can exit non-zero, so a release
 * or a CI run can be stopped by it. And rendering several hundred pages inside a
 * web request is how you meet a timeout: here it can take as long as it takes.
 */
class CheckSite extends Command
{
    use RunsInPlease;

    protected $signature = 'statamic:a11y:check
        {--collection=* : Only these collections. Default is all of them.}
        {--drafts : Include entries that are not published.}';

    protected $description = 'Check every page for accessibility problems, grouped by how many pages each one is on.';

    public function handle(SiteScanner $scanner): int
    {
        $entries = $this->entries();

        $this->line('');
        $report = $scanner->scan($entries, function ($entry) {
            $this->line('  <fg=gray>checking</> '.($entry->url() ?? $entry->id()));
        });

        $this->line('');
        $this->write($report);

        // Non-zero on errors or on a page that could not be read. A build that
        // went green because four pages failed to render would be worse than no
        // check at all.
        return $report->shouldFail() ? 1 : 0;
    }

    /**
     * @return array<int, \Statamic\Contracts\Entries\Entry>
     */
    private function entries(): array
    {
        $query = Entry::query();

        if ($collections = $this->option('collection')) {
            $query->whereIn('collection', $collections);
        }

        if (! $this->option('drafts')) {
            $query->where('published', true);
        }

        // Anything without a URL is not a page the site serves, so it is not
        // dropped quietly: it never was in scope.
        return $query->get()->filter(fn ($entry) => (bool) $entry->url())->values()->all();
    }

    private function write(ScanReport $report): void
    {
        $this->line("Checked {$report->pagesChecked} pages.");
        $this->line('');

        if ($report->findings === [] && $report->unreadable === []) {
            $this->info('Nothing found.');
            $this->closing();

            return;
        }

        $this->section('Must be fixed before publishing', $report->errors(), $report->pagesChecked);
        $this->section('Worth a look', $report->warnings(), $report->pagesChecked);

        if ($report->unreadable !== []) {
            $this->line('<fg=red>Could not be checked at all</>');
            $this->line('');

            foreach ($report->unreadable as $url => $reason) {
                $this->line("  {$url}");
                $this->line("    <fg=gray>{$reason}</>");
            }

            $this->line('');
        }

        $this->closing();
    }

    /**
     * @param  array<int, ScannedFinding>  $findings
     */
    private function section(string $heading, array $findings, int $pagesChecked): void
    {
        if ($findings === []) {
            return;
        }

        $this->line("<options=bold>{$heading}</>");
        $this->line('');

        foreach ($findings as $finding) {
            $reach = $finding->reach($pagesChecked);

            $this->line(sprintf('  <options=bold>%-12s</> %s', $reach, $finding->violation->cta));
            $this->line('    <fg=gray>'.$finding->violation->message.'</>');

            if ($finding->violation->pointer !== '') {
                $this->line('    <fg=gray>'.$finding->violation->pointer.'</>');
            }

            // One page gets named. Forty pages get counted, because a wall of
            // URLs is how somebody stops reading the thing they asked for.
            if ($finding->pageCount() === 1) {
                $this->line('    <fg=gray>'.$finding->pages[0].'</>');
            }

            $this->line('');
        }
    }

    /**
     * The same refusal the rest of the addon makes, in the place a developer
     * reads it. A green run here is not a clean bill, and the biggest thing this
     * cannot see is the one every other tool checks.
     */
    private function closing(): void
    {
        $this->line('<fg=gray>This reads the finished pages. It cannot see anything your stylesheet</>');
        $this->line('<fg=gray>decides, colour contrast included, and a page it finds nothing wrong</>');
        $this->line('<fg=gray>with has not been proven accessible.</>');
        $this->line('');
    }
}
