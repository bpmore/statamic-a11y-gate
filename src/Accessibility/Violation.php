<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * One accessibility issue found in a rendered page.
 *
 * The message is plain language, resolved from the rule by `Remediation`,
 * because guided fixing is the product: an author is shown "This image needs a
 * description", never a rule id. Errors refuse the publish; warnings do not.
 *
 * `wcag` is the label the finding is allowed to cite, and for several rules it
 * is deliberately not a success criterion. See `Remediation` for which, and why.
 *
 * Narrower than Windrow's Violation by four fields. Windrow carries `blockUid`,
 * `fieldKey` and `breakpoint` so its editor can highlight the block and focus
 * the control that produced an issue. Statamic has no blocks and no editor
 * fields of that shape, so those fields could only ever be empty strings here,
 * and an always-empty field is an invitation to write code that pretends to
 * read it.
 */
final class Violation
{
    public const ERROR = 'error';

    public const WARN = 'warn';

    public function __construct(
        public readonly string $rule,
        public readonly string $severity,
        public readonly string $wcag,
        public readonly string $message,
        public readonly string $cta,
        public readonly string $pointer = '',
    ) {}

    public function isError(): bool
    {
        return $this->severity === self::ERROR;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'severity' => $this->severity,
            'wcag' => $this->wcag,
            'message' => $this->message,
            'cta' => $this->cta,
            'pointer' => $this->pointer,
        ];
    }
}
