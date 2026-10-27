<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Contracts;

/**
 * What a contributor said about one save: let it through, or stop it with
 * these lines for the author.
 *
 * The lines are the contributor's own words, shown as written, so they are
 * written to be read as a list by the person who has to fix the page.
 */
final class ContributedResult
{
    /**
     * @param  list<string>  $lines
     */
    private function __construct(
        public readonly bool $blocks,
        public readonly array $lines,
    ) {}

    public static function passes(): self
    {
        return new self(false, []);
    }

    /**
     * @param  list<string>  $lines  what to fix, the first line able to stand on its own
     */
    public static function blocks(array $lines): self
    {
        return new self(true, array_values(array_map(strval(...), $lines)));
    }
}
