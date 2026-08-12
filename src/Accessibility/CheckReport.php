<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * What the checker found, and how much of the page it could see while finding it.
 *
 * The two halves travel together on purpose. A list of findings on its own
 * invites the reading "nothing else is wrong", and for this checker that reading
 * is false on almost every page: four of the seven families are blind or
 * half-blind without markup a Statamic site does not produce. Anything that
 * shows the findings has the coverage in its hand already, and has to work to
 * hide it.
 */
final class CheckReport
{
    /**
     * @param  array<int, Violation>  $violations
     * @param  array<int, Coverage>  $coverage
     */
    public function __construct(
        public readonly array $violations,
        public readonly array $coverage,
    ) {}

    /** @return array<int, Coverage> */
    public function fully(): array
    {
        return $this->withExtent(Coverage::FULL);
    }

    /** @return array<int, Coverage> */
    public function partly(): array
    {
        return $this->withExtent(Coverage::PARTIAL);
    }

    /** @return array<int, Coverage> */
    public function notAtAll(): array
    {
        return $this->withExtent(Coverage::NONE);
    }

    /**
     * The gaps worth telling the person writing the page about: things in their
     * own content that only they can settle.
     *
     * Deliberately much shorter than the coverage list. Everything else in there
     * is either a standing limit of the tool or a matter for whoever set the site
     * up, and putting those in front of an author on every entry is how the one
     * line that matters gets scrolled past.
     *
     * @return array<int, string>
     */
    public function notices(): array
    {
        $notices = [];

        foreach ($this->coverage as $entry) {
            if ($entry->notice !== '') {
                $notices[] = $entry->notice;
            }
        }

        return $notices;
    }

    /**
     * One sentence an auditor can read, and the sentence this product is sold on
     * being willing to say. It counts checks; it never scores the page.
     */
    public function summary(): string
    {
        $total = count($this->coverage);
        $parts = [count($this->fully()).' of '.$total.' checks ran in full'];

        if (($partly = count($this->partly())) > 0) {
            $parts[] = $partly.' ran partly';
        }

        if (($none = count($this->notAtAll())) > 0) {
            $parts[] = $none.' could not run here';
        }

        return implode(', ', $parts).'.';
    }

    /** @return array<int, Coverage> */
    private function withExtent(string $extent): array
    {
        return array_values(array_filter($this->coverage, fn (Coverage $c) => $c->extent === $extent));
    }
}
