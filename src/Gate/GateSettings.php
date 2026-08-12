<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;

/**
 * The addon's settings, read once and passed around as a value.
 *
 * A value object rather than `config()` calls scattered through the gate, so a
 * test can state the setting it is testing instead of editing global state, and
 * so an unknown value is rejected here rather than acted on somewhere quieter.
 */
final class GateSettings
{
    public const REFUSE = 'refuse';

    public const WARN = 'warn';

    /**
     * @param  array<int, string>  $collections  handles to gate; empty means every collection
     * @param  array<int, string>  $optedIn  keys of the opt-in checks this site stamps markup for
     */
    public function __construct(
        public readonly string $mode = self::REFUSE,
        public readonly array $collections = [],
        public readonly array $optedIn = [],
        public readonly AccessibilityStandard $standard = new AccessibilityStandard('wcag22aa', 'WCAG 2.2 Level AA', 24),
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $mode = $config['mode'] ?? self::REFUSE;

        // An unrecognised mode fails closed rather than falling through to warn.
        // A typo in a config file must never be the reason a gate stopped
        // gating, because nothing in the interface would show it had.
        if ($mode !== self::WARN) {
            $mode = self::REFUSE;
        }

        return new self(
            mode: $mode,
            collections: array_values(array_filter((array) ($config['collections'] ?? []))),
            optedIn: array_values(array_filter((array) ($config['opt_in_checks'] ?? []))),
            standard: ($config['standard'] ?? 'wcag22aa') === 'wcag22aaa'
                ? AccessibilityStandard::wcag22aaa()
                : AccessibilityStandard::wcag22aa(),
        );
    }

    public function refuses(): bool
    {
        return $this->mode === self::REFUSE;
    }

    public function gates(string $collectionHandle): bool
    {
        return $this->collections === [] || in_array($collectionHandle, $this->collections, true);
    }
}
