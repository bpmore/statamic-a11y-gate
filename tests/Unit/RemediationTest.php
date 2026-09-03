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

it('has somewhere to send the author for every criterion a rule cites, and nowhere for a house rule', function () {
    // A rule that starts citing a criterion the reference table does not
    // know would ship a finding with no link and nothing said. Held here so
    // the table and the rules move together.
    foreach (\Bpmore\A11yGate\Accessibility\Remediation::RULES as $rule => $copy) {
        $reference = \Bpmore\A11yGate\Accessibility\CriterionReference::for($copy['wcag']);

        if (str_starts_with($copy['wcag'], 'WCAG ')) {
            expect($reference)->not->toBeNull("{$rule} cites {$copy['wcag']}, which has no reference");
            expect($reference['url'])->toBe('https://www.w3.org/WAI/WCAG22/Understanding/'.substr($reference['url'], strlen('https://www.w3.org/WAI/WCAG22/Understanding/')), "{$rule} links to the W3C");
            expect('WCAG '.$reference['number'])->toBe($copy['wcag']);
            expect($reference['name'])->not->toBe('');
        } else {
            expect($reference)->toBeNull("{$rule} is a house rule and must not link to a criterion");
        }
    }

    expect(\Bpmore\A11yGate\Accessibility\CriterionReference::for('WCAG 1.1.1'))->toBe([
        'number' => '1.1.1',
        'name' => 'Non-text Content',
        'url' => 'https://www.w3.org/WAI/WCAG22/Understanding/non-text-content.html',
    ]);
    // A criterion no rule cites is not linked, however real: the gate does
    // not send anybody to read up on what it did not check.
    expect(\Bpmore\A11yGate\Accessibility\CriterionReference::for('WCAG 1.4.3'))->toBeNull();
    expect(\Bpmore\A11yGate\Accessibility\CriterionReference::for('Heading structure'))->toBeNull();
});
