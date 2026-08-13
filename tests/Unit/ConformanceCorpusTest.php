<?php

declare(strict_types=1);

use Bpmore\A11yGate\Accessibility\Checks\CheckPack;
use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Accessibility\Violation;

/**
 * The corpus, run against the checker.
 *
 * `corpus/` holds fixed pages and the exact findings each must produce, so that
 * a rule cannot change what it reports without a test saying so by name. This
 * file is the thing that makes it binding rather than decorative.
 *
 * Behaviour changes only when somebody edits the corpus, in its own commit, with
 * the reason written down.
 *
 * These fixtures were once shared with another implementation of the same rules,
 * and this file was close to a copy of that project's. That ended when the addon
 * was separated to stand on its own. What did not change is what the assertions
 * are for, which is why they are unchanged.
 *
 * Helpers are prefixed with their subject: Pest shares one global function
 * namespace across every test file.
 */
function corpusCases(): array
{
    $cases = [];

    // Resolved from __DIR__ rather than from a framework path helper: this
    // loader is shared with a Laravel application and must not need a booted
    // framework to find its own fixtures.
    foreach (glob(dirname(__DIR__, 2).'/corpus/cases/*', GLOB_ONLYDIR) as $dir) {
        $cases[basename($dir)] = [
            'html' => file_get_contents("$dir/input.html"),
            'expected' => json_decode(file_get_contents("$dir/expected.json"), true),
        ];
    }

    ksort($cases);

    return $cases;
}

/** @return array<int, array<string, string>> */
function corpusFindings(string $html): array
{
    return array_map(fn (Violation $v) => [
        // Pinned: the decision. A rule, how bad it is, and what it is allowed to
        // cite. Not pinned: the message and call to action, which are wording and
        // must stay free to improve, because pinning prose makes every improved
        // sentence look like a behaviour change.
        'rule' => $v->rule,
        'severity' => $v->severity,
        'wcag' => $v->wcag,
    ], (new StaticAccessibilityChecker)->check($html));
}

it('has a corpus at all', function () {
    // The failure this catches is the corpus directory going missing or being
    // renamed, which would turn every assertion below into a loop over nothing
    // and report a clean pass. A vacuous suite is the worst outcome here,
    // because the whole point is catching silent divergence.
    expect(count(corpusCases()))->toBeGreaterThanOrEqual(24);
});

it('produces exactly the findings the corpus expects, in order', function () {
    // Order is part of the contract. A refusal lists its issues in the order it
    // receives them, so two projects returning the same findings in a different
    // sequence show authors different things first.
    foreach (corpusCases() as $name => $case) {
        expect(corpusFindings($case['html']))
            ->toBe($case['expected']['findings'], "corpus case '$name' no longer matches");
    }
});

it('covers every rule the pack can raise', function () {
    // A corpus that covered a third of the rules would let the two projects
    // diverge on the other two thirds without a single test going red.
    $covered = [];

    foreach (corpusCases() as $case) {
        foreach ($case['expected']['findings'] as $finding) {
            $covered[$finding['rule']] = true;
        }
    }

    $uncovered = array_values(array_diff(CheckPack::rules(), array_keys($covered)));

    expect($uncovered)->toBe([], 'these rules have no corpus case, so nothing would notice them changing: '.implode(', ', $uncovered));
});

it('says which cases a site without the extra markup cannot reproduce', function () {
    // The honesty rule, enforced. Several rules read data-a11y-* attributes that
    // a site has to stamp into its own templates, so a site that has not done
    // that finds nothing for them. That is fine and it is stated. What is NOT
    // case that quietly finds nothing while the corpus implies it should, which
    // is exactly how "4 of 7 checks ran" degrades into a clean tick.
    foreach (corpusCases() as $name => $case) {
        // array_key_exists rather than toHaveKey('portability', $message):
        // toHaveKey's second argument is the expected VALUE, not a message, so
        // that spelling asserts portability === "corpus case ... does not
        // declare its portability" and fails on every well-formed case. Same
        // family as the toContain trap in CLAUDE.md.
        expect(array_key_exists('portability', $case['expected']))
            ->toBeTrue("corpus case '$name' does not declare its portability");

        expect(in_array($case['expected']['portability'], ['portable', 'host-markup', 'host-styling'], true))
            ->toBeTrue("corpus case '$name' declares an unknown portability: {$case['expected']['portability']}");
    }
});

it('marks every case that depends on markup the site has to stamp', function () {
    // Derived from the fixture rather than trusted from the label: a case whose
    // HTML carries a data-a11y-* attribute needs that markup whatever its
    // metadata claims. This is the assertion that catches somebody labelling such
    // a case portable, which would tell every site without the markup that it
    // must reproduce a finding it has no way to produce.
    foreach (corpusCases() as $name => $case) {
        $needsStampedMarkup = str_contains($case['html'], 'data-a11y-');

        if ($needsStampedMarkup) {
            expect($case['expected']['portability'])
                ->toBe('host-markup', "corpus case '$name' uses data-a11y- markup but is not labelled host-markup");
        }
    }
});

it('keeps a clean page clean', function () {
    // Stated separately from the loop because it is the assertion most likely to
    // be lost in a rewrite, and it is the one that catches a rule firing where
    // nothing is wrong. A gate with a false positive gets switched off.
    expect(corpusFindings(corpusCases()['clean-page']['html']))->toBe([]);
});
