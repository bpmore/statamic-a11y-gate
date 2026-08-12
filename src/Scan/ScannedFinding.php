<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Scan;

use Bpmore\A11yGate\Accessibility\Violation;

/**
 * One problem, and every page it appears on.
 *
 * Findings are the same problem when they share a rule and a pointer. That pair
 * is what makes the grouping useful rather than arbitrary: "link-unclear" on its
 * own would merge every bad link on the site into one line, and adding the page
 * would merge nothing at all. The pointer is the text or the file that tripped
 * the rule, so the same card grid on forty pages collapses to one line and forty
 * different undescribed images stay forty lines, which is correct in both cases.
 */
final class ScannedFinding
{
    /**
     * @param  array<int, string>  $pages  URLs, in the order they were scanned
     */
    public function __construct(
        public readonly Violation $violation,
        public readonly array $pages,
    ) {}

    public function isError(): bool
    {
        return $this->violation->isError();
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    /**
     * The key two findings must share to be the same problem.
     */
    public static function keyFor(Violation $violation): string
    {
        return $violation->rule."\0".$violation->pointer;
    }

    /**
     * How widespread this is, for somebody reading a list rather than a table.
     *
     * "every page" is said only when it is literally true of the pages scanned,
     * because it is the phrase that turns a list of findings into a decision
     * about the theme, and it has to be earned.
     */
    public function reach(int $pagesChecked): string
    {
        if ($this->pageCount() >= $pagesChecked && $pagesChecked > 0) {
            return 'every page';
        }

        return $this->pageCount() === 1 ? '1 page' : $this->pageCount().' pages';
    }
}
