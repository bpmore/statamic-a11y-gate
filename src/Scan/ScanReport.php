<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Scan;

/**
 * What a whole-site scan found, grouped by how many pages each problem is on.
 *
 * The grouping is the entire point, and it is the one thing a page-by-page
 * checker cannot tell you. **A problem on one page is something somebody wrote.
 * The same problem on every page came from a template.** An author fixes the
 * first. Only a developer can fix the second, and until they do it is wrong
 * everywhere at once.
 *
 * That distinction is a count, not a judgement. This class reports how many
 * pages carry each finding and leaves "so it is probably the theme" to the
 * person reading, because a checker that reads HTML cannot see a template and
 * should not claim to.
 */
final class ScanReport
{
    /**
     * @param  array<int, ScannedFinding>  $findings  most widespread first
     * @param  array<string, string>  $unreadable  page URL => why it could not be checked
     */
    public function __construct(
        public readonly int $pagesChecked,
        public readonly array $findings,
        public readonly array $unreadable = [],
    ) {}

    /** @return array<int, ScannedFinding> */
    public function errors(): array
    {
        return array_values(array_filter($this->findings, fn (ScannedFinding $f) => $f->isError()));
    }

    /** @return array<int, ScannedFinding> */
    public function warnings(): array
    {
        return array_values(array_filter($this->findings, fn (ScannedFinding $f) => ! $f->isError()));
    }

    /**
     * Whether a build should stop.
     *
     * A page that could not be rendered counts, and that is deliberate: the
     * command exists to be run before something ships, and "we could not look at
     * four of your pages" is not a pass. It is the same fail-closed rule the
     * publish gate follows.
     */
    public function shouldFail(): bool
    {
        return $this->errors() !== [] || $this->unreadable !== [];
    }
}
