<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

use Bpmore\A11yGate\Accessibility\CheckReport;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * What the gate found, and whether it managed to look.
 *
 * Three outcomes, and the third is the one this class exists for. "Nothing was
 * wrong" and "nothing could be checked" are different answers, and a gate that
 * returned an empty list for both would be indistinguishable from a gate that
 * passed a page it never rendered. Every caller has to handle `couldNotCheck`
 * explicitly, because there is no shape of this object that lets it be mistaken
 * for a clean result.
 */
final class GateResult
{
    private const NOT_APPLICABLE = 'not-applicable';

    private const CHECKED = 'checked';

    private const COULD_NOT_CHECK = 'could-not-check';

    /**
     * @param  array<int, Violation>  $violations
     * @param  array<int, Coverage>  $coverage
     */
    private function __construct(
        public readonly string $outcome,
        public readonly array $violations,
        public readonly string $reason,
        public readonly array $coverage = [],
        public readonly string $coverageSummary = '',
    ) {}

    /**
     * There was nothing here to check: a draft, a collection the site chose not
     * to gate, or an entry with no page of its own.
     */
    public static function notApplicable(string $reason): self
    {
        return new self(self::NOT_APPLICABLE, [], $reason);
    }

    /**
     * A checked page carries its coverage, not just its findings.
     *
     * There is no constructor here that takes findings on their own, on purpose.
     * A caller that wanted to report a clean page without saying how much of it
     * was looked at would have to add one, and adding it would be a visible
     * decision rather than an omission.
     */
    public static function checked(CheckReport $report): self
    {
        return new self(
            self::CHECKED,
            $report->violations,
            '',
            $report->coverage,
            $report->summary(),
        );
    }

    /** The page could not be produced, so nothing about it is known. */
    public static function couldNotCheck(string $reason): self
    {
        return new self(self::COULD_NOT_CHECK, [], $reason);
    }

    public function wasChecked(): bool
    {
        return $this->outcome === self::CHECKED;
    }

    public function couldNotBeChecked(): bool
    {
        return $this->outcome === self::COULD_NOT_CHECK;
    }

    /**
     * Whether a gate in refuse mode should stop this save.
     *
     * A page that could not be rendered stops it too. That is the fail-closed
     * half of the product: allowing a save because the check broke would publish
     * on the strength of a check nobody ran.
     */
    public function shouldRefuse(): bool
    {
        return $this->couldNotBeChecked() || $this->errors() !== [];
    }

    /** @return array<int, Violation> */
    public function errors(): array
    {
        return array_values(array_filter($this->violations, fn (Violation $v) => $v->isError()));
    }

    /** @return array<int, Violation> */
    public function warnings(): array
    {
        return array_values(array_filter($this->violations, fn (Violation $v) => ! $v->isError()));
    }
}
