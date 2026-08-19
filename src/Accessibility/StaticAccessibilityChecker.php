<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

use Bpmore\A11yGate\Accessibility\Checks\CheckPack;
use DOMDocument;
use DOMXPath;

/**
 * The checker: HTML in, findings out.
 *
 * Framework-free on purpose, and the rule is worth restating where somebody
 * might be tempted to break it. No container, no facades, no models. The gate
 * that refuses a Statamic publish is a listener that calls this; a build-time
 * command would be another caller; a test is a third. None of them are visible
 * from in here.
 *
 * What it cannot do is as much of the product as what it can. It reads rendered
 * markup, so it cannot see a size or a colour that comes from a stylesheet, it
 * cannot judge whether a description is any good, and it cannot establish
 * conformance with anything. A browser pass with axe catches more.
 */
final class StaticAccessibilityChecker
{
    /**
     * The findings, and how much of the page each family could see while
     * looking. Everything that shows a result to a person should call this.
     */
    /**
     * @param  array<int, string>  $optedIn  keys of the opt-in checks this site stamps for
     */
    public function report(string $html, ?AccessibilityStandard $standard = null, array $optedIn = []): CheckReport
    {
        $xpath = $this->xpath($html);

        $violations = [];
        $coverage = [];

        foreach (CheckPack::all() as $check) {
            // Every check still runs, including the opt-in ones a site has not
            // integrated. They find nothing without their markup, and on the day
            // a site starts stamping without changing a setting, its findings
            // arrive rather than being quietly withheld. Only the coverage line
            // is opt-in, because that is the part that would otherwise repeat
            // itself on every page forever.
            $violations = [...$violations, ...$check->run($xpath, $standard)];

            $entry = $check->coverage($xpath);

            // The one place the opt-in setting is read. An opt-in check on a
            // site that never integrated it has nothing to say about any page,
            // ever, and repeating that on every entry is noise rather than
            // honesty: an author with no plain-language summaries does not need
            // telling, forever, that summaries were not checked.
            //
            // This used to be two hand-written copies of the same `in_array`,
            // one inside each opt-in check, while `needsOptIn()` declared the
            // same fact on the contract and was read by nobody. Two
            // representations, and the typed one was the inert one.
            //
            // Keyed on `none` rather than on "not full", deliberately. A check
            // that ran and found part of what it judges invisible has something
            // real to report even on a site that never opted in. Neither opt-in
            // check can return `partial` today, so nothing turns on it yet.
            //
            // **The `needsOptIn()` term is not exercised by anything, and it
            // stays.** Deleting it passes the whole suite, because no check that
            // is not opt-in can return `none`: three always answer `full`, and
            // the other two answer `full` or `partial`. What it guards is the
            // check that does not exist yet, one which is blind on some pages
            // for its own reasons rather than for want of an integration. Such a
            // check would be silently dropped on every site that had not listed
            // it in a setting it has no business being in, which is the silent
            // zero this file exists to refuse. A test for it would need a fake
            // check, and `CheckPack` is a hard-coded list on purpose, so the
            // honest thing is to say it is unproven rather than imply otherwise.
            if ($entry->extent === Coverage::NONE
                && $check::needsOptIn()
                && ! in_array($check::key(), $optedIn, true)) {
                continue;
            }

            $coverage[] = $entry;
        }

        return new CheckReport($violations, $coverage);
    }

    /**
     * The findings alone.
     *
     * Kept because the corpus is about findings and nothing else. Coverage is
     * reporting rather than a verdict, and pinning it would make every
     * improvement to how coverage is described look like a behaviour change.
     * Nothing that shows a result to a person should use this.
     *
     * @return array<int, Violation>
     */
    public function check(string $html, ?AccessibilityStandard $standard = null): array
    {
        return $this->report($html, $standard)->violations;
    }

    private function xpath(string $html): DOMXPath
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        // libxml assumes Latin-1 unless the markup declares a charset; the XML
        // prolog forces UTF-8 so "×", "→" and accents survive into accessible
        // names, where a mangled byte would change what a link is compared as.
        $dom->loadHTML('<?xml encoding="utf-8"?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($dom);
    }
}
