<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Scan;

use Bpmore\A11yGate\Accessibility\Violation;
use Bpmore\A11yGate\Gate\GateResult;
use Bpmore\A11yGate\Gate\PublishGate;
use Statamic\Contracts\Entries\Entry;

/**
 * Every page, checked once, with the findings grouped.
 *
 * It calls the same `PublishGate::examine()` the panel calls, on purpose. A
 * second way of checking a page is a second set of answers, and the only thing
 * worse than one accessibility tool is two that disagree.
 *
 * Deliberately not the gate's `inspect()`: this scans what a site actually
 * serves, so a draft is skipped for having no live page rather than for being
 * uninteresting, and the collection filter that the gate honours does not apply.
 * Somebody scanning a site wants to know what is out there, not what this addon
 * was configured to police.
 */
final class SiteScanner
{
    public function __construct(private readonly PublishGate $gate) {}

    /**
     * @param  iterable<Entry>  $entries
     * @param  null|callable(Entry): void  $onPage  called before each page, for progress
     */
    public function scan(iterable $entries, ?callable $onPage = null): ScanReport
    {
        $pages = 0;
        $unreadable = [];

        /** @var array<string, array{violation: Violation, pages: array<int, string>}> $grouped */
        $grouped = [];

        foreach ($entries as $entry) {
            if ($onPage) {
                $onPage($entry);
            }

            $result = $this->gate->examine($entry);
            $url = (string) ($entry->url() ?? $entry->id());

            if ($result->couldNotBeChecked()) {
                // Counted, named, and never folded into the clean total. A page
                // the scanner could not read is the one page it can say nothing
                // about, and a report that quietly dropped it would be claiming
                // more coverage than it had.
                $unreadable[$url] = $result->reason;

                continue;
            }

            if (! $result->wasChecked()) {
                // No page of its own: nothing was served, so there is nothing to
                // scan and nothing to report.
                continue;
            }

            $pages++;

            foreach ($result->violations as $violation) {
                $key = ScannedFinding::keyFor($violation);

                $grouped[$key] ??= ['violation' => $violation, 'pages' => []];
                $grouped[$key]['pages'][] = $url;
            }
        }

        return new ScanReport($pages, $this->sorted($grouped), $unreadable);
    }

    /**
     * Most widespread first, and errors before warnings within that.
     *
     * The order is the argument. A problem on forty pages is one template fix
     * and forty pages of benefit; a problem on one page is one author's
     * afternoon. Sorting the other way buries the expensive one.
     *
     * @param  array<string, array{violation: Violation, pages: array<int, string>}>  $grouped
     * @return array<int, ScannedFinding>
     */
    private function sorted(array $grouped): array
    {
        $findings = array_map(
            fn (array $g) => new ScannedFinding($g['violation'], $g['pages']),
            array_values($grouped),
        );

        usort($findings, function (ScannedFinding $a, ScannedFinding $b) {
            return [$b->isError(), $b->pageCount()] <=> [$a->isError(), $a->pageCount()];
        });

        return $findings;
    }
}
