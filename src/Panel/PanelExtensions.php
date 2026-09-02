<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Panel;

use Statamic\Contracts\Entries\Entry;
use Throwable;

/**
 * What another addon may add to the panel: a block about this page, drawn
 * beneath whatever the gate itself has to say.
 *
 * The gate checks the page as it stands, right now, unsaved changes included.
 * It knows nothing about the page's history, and it should not: a rule that
 * reads a database is not a rule that reads HTML. But a companion that keeps
 * that history, such as a scanner that reads the whole site on a schedule,
 * has something the author would want to see in the same place: what the
 * last scan found on this page, and whether it is still open. This is the
 * seam for that, and the gate's own rules do not change by its existence.
 *
 * A provider is a callable taking the entry and returning a block, or null
 * for nothing to say. A block is a heading, plain lines, and an optional link.
 * A provider that throws is drawn as a block saying so rather than dropped,
 * because a panel that went quiet about a broken companion would look like a
 * page with nothing else to say, which is the silence this addon refuses.
 */
final class PanelExtensions
{
    /** @var array<int, callable(Entry): (array|null)> */
    private static array $providers = [];

    /**
     * @param  callable(Entry): (array|null)  $provider
     */
    public static function register(callable $provider): void
    {
        self::$providers[] = $provider;
    }

    public static function flush(): void
    {
        self::$providers = [];
    }

    /**
     * @return array<int, array{heading: string, lines: array<int, string>, link: array{url: string, text: string}|null, tone: string}>
     */
    public static function for(Entry $entry): array
    {
        $blocks = [];

        foreach (self::$providers as $provider) {
            try {
                $block = $provider($entry);
            } catch (Throwable $e) {
                $block = [
                    'heading' => 'Another addon could not answer for this page',
                    'lines' => [$e->getMessage() !== '' ? $e->getMessage() : $e::class],
                    'tone' => 'warning',
                ];
            }

            if ($block === null) {
                continue;
            }

            $blocks[] = self::normalise((array) $block);
        }

        return $blocks;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array{heading: string, lines: array<int, string>, link: array{url: string, text: string}|null, tone: string}
     */
    private static function normalise(array $block): array
    {
        $link = is_array($block['link'] ?? null) && ! empty($block['link']['url'])
            ? ['url' => (string) $block['link']['url'], 'text' => (string) ($block['link']['text'] ?? 'Open')]
            : null;

        return [
            'heading' => (string) ($block['heading'] ?? ''),
            'lines' => array_values(array_map('strval', array_filter((array) ($block['lines'] ?? []), fn ($l) => is_scalar($l) && (string) $l !== ''))),
            'link' => $link,
            'tone' => in_array($block['tone'] ?? null, ['default', 'warning', 'error'], true) ? $block['tone'] : 'default',
        ];
    }
}
