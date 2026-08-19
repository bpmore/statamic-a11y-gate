<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\Checks\CheckPack;
use Bpmore\A11yGate\Accessibility\Remediation;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The rule table, held to the pack that raises from it.
 *
 * `Remediation::RULES` and `Check::rules()` are two hand-written lists of the
 * same rule ids, and nothing tied them together. They agreed, and no test said
 * so. Now that severity lives in the table as well as copy, a rule in one list
 * and not the other is worse than untidy: `Remediation::violation()` reads
 * `self::RULES[$rule]` with no guard, so a rule the pack can raise but the table
 * does not carry is a fatal during a publish.
 */
it('carries copy for every rule the pack can raise, and no others', function () {
    $table = array_keys(Remediation::RULES);
    sort($table);

    // CheckPack::rules() already sorts and uniques.
    expect($table)->toBe(CheckPack::rules());
});

it('gives every rule a severity the product recognises', function () {
    // Severity is a plain string on `Violation`, and `GateResult::errors()`
    // partitions on `! isError()`, so anything other than the exact literal
    // 'error' is silently classified as a warning and stops refusing. A typo
    // here would turn a refusal into a warning with nothing said anywhere,
    // which is the one transition this addon is not allowed to make quietly.
    $unknown = [];

    foreach (Remediation::RULES as $rule => $copy) {
        if (! in_array($copy['severity'], [Violation::ERROR, Violation::WARN], true)) {
            $unknown[] = $rule.' => '.var_export($copy['severity'], true);
        }
    }

    expect($unknown)->toBe([], 'these rules carry a severity nothing reads: '.implode(', ', $unknown));
});

it('refuses on everything except the four findings that are meant to warn', function () {
    // Pinned by name rather than by count, because which rules stop a publish
    // is the product. The corpus pins the same fact per rule; this says it once
    // in a place somebody can read in five seconds, and it is what a reviewer
    // checks a severity change against.
    $warns = [];

    foreach (Remediation::RULES as $rule => $copy) {
        if ($copy['severity'] === Violation::WARN) {
            $warns[] = $rule;
        }
    }

    sort($warns);

    expect($warns)->toBe([
        // No success criterion covers a link with no destination, and a page
        // half-written usually has one.
        'link-goes-nowhere',
        // Blocking both of a pair of pages that link to each other would
        // deadlock the pair.
        'link-unpublished-page',
        // The verdict rests on a word list rather than on the markup.
        'link-vague',
        // Flesch-Kincaid cannot tell jargon made of short words from plain
        // English, so blocking on it would be blocking on a guess.
        'reading-level-high',
    ]);
});
