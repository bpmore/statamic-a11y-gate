<?php

declare(strict_types=1);

/**
 * Every class the addon writes exists in the control panel's built stylesheet.
 *
 * The control panel ships a compiled Tailwind build, and a class it does not
 * contain is dropped without a word: no error, no warning, just the styling
 * that class was meant to give, missing. That is how the Tools page drew its
 * lists with no bullets for as long as `list-disc` was on them, and how a link
 * in the panel once shipped looking like plain text under `max-w-32`. Both were
 * found by eye, on a live site, long after the fact.
 *
 * So the built stylesheet is read here and every class token in the two files
 * that carry classes is looked up in it. A class that is not there fails this
 * test with its name, which is the sentence nobody got at the time.
 *
 * Tailwind writes a class as a selector with its punctuation escaped, so
 * `dark:text-blue-300` is `.dark\:text-blue-300` in the file. The lookup
 * escapes the same way and then requires the selector to end there, so
 * `space-y-2` cannot be satisfied by `space-y-2xl`.
 */
function builtStylesheet(): string
{
    $files = glob(dirname(__DIR__, 2).'/vendor/statamic/cms/resources/dist/build/assets/*.css') ?: [];

    expect($files)->not->toBe([], 'the control panel build is missing from vendor, so nothing can be checked');

    return implode("\n", array_map(fn (string $f) => (string) file_get_contents($f), $files));
}

/**
 * @return array<int, string>
 */
function classesIn(string $source): array
{
    preg_match_all('/class="([^"]+)"/', $source, $matches);

    $classes = [];

    foreach ($matches[1] as $list) {
        foreach (preg_split('/\s+/', trim($list)) ?: [] as $class) {
            if ($class !== '') {
                $classes[$class] = true;
            }
        }
    }

    return array_keys($classes);
}

foreach ([
    'the Tools page' => 'resources/views/utilities/gate.blade.php',
    'the panel' => 'resources/js/a11y-panel.js',
] as $what => $file) {
    it("uses only classes the control panel build has, in {$what}", function () use ($file) {
        $css = builtStylesheet();
        $classes = classesIn((string) file_get_contents(dirname(__DIR__, 2).'/'.$file));

        // A file with no classes would pass vacuously. Both files have some,
        // and a rewrite that dropped them all should be looked at.
        expect($classes)->not->toBe([], "{$file} has no class attributes to check");

        $missing = [];

        foreach ($classes as $class) {
            $escaped = preg_quote(preg_replace('/([:\/.\[\]!%#])/', '\\\\$1', $class), '/');

            if (! preg_match('/\.'.$escaped.'(?![\w-])/', $css)) {
                $missing[] = $class;
            }
        }

        expect($missing)->toBe([], "{$file} uses classes the control panel build does not have, so they draw nothing: ".implode(', ', $missing));
    });
}
