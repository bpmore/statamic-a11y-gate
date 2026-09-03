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
 * for nothing to say. A block is a heading, plain lines, an optional link,
 * and an optional mark: a small image with the words that stand in for it,
 * so a companion that speaks here can be recognised as the site owner's own.
 * A provider that throws is drawn as a block saying so rather than dropped,
 * because a panel that went quiet about a broken companion would look like a
 * page with nothing else to say, which is the silence this addon refuses.
 *
 * A mark is drawn, never interpreted. The gate holds no branding of its own
 * and has no setting for one: it draws what a provider hands it, subject to
 * the two rules below, and a site with nothing but the gate on it looks
 * exactly as it did before this existed.
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
     * What a mark's address may start with.
     *
     * An `<img src>` is not a script, and a drawing loaded through one cannot
     * run any, but the address still reaches the browser as written. Anything
     * outside this list, `javascript:` above all, is dropped rather than
     * drawn: the panel is an addon's to fill and the page is the control
     * panel's, and one must not be able to put the other at risk.
     */
    private const MARK_SCHEMES = ['/', 'https://', 'http://', 'data:image/'];

    /**
     * @return array<int, array{heading: string, lines: array<int, string>, link: array{url: string, text: string}|null, tone: string, mark: array{url: string, alt: string}|null}>
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
     * @return array{heading: string, lines: array<int, string>, link: array{url: string, text: string}|null, tone: string, mark: array{url: string, alt: string}|null}
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
            'mark' => self::mark($block['mark'] ?? null),
        ];
    }

    /**
     * A block's mark, or null.
     *
     * Two rules, and a mark that breaks either is left out rather than drawn
     * badly. It needs words that stand in for it, because an image with no
     * text alternative inside an accessibility addon's own panel is the fault
     * this addon exists to refuse, on its own screen. And its address has to
     * be one of the shapes above.
     *
     * @return array{url: string, alt: string}|null
     */
    private static function mark(mixed $mark): ?array
    {
        if (! is_array($mark)) {
            return null;
        }

        $url = is_string($mark['url'] ?? null) ? trim($mark['url']) : '';
        $alt = is_string($mark['alt'] ?? null) ? trim($mark['alt']) : '';

        if ($url === '' || $alt === '') {
            return null;
        }

        foreach (self::MARK_SCHEMES as $scheme) {
            if (str_starts_with(strtolower($url), $scheme)) {
                return ['url' => $url, 'alt' => $alt];
            }
        }

        return null;
    }
}
