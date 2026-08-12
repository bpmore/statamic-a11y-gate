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

            if ($entry = $check->coverage($xpath, $optedIn)) {
                $coverage[] = $entry;
            }
        }

        return new CheckReport($violations, $coverage);
    }

    /**
     * The findings alone.
     *
     * Kept because the shared conformance corpus is about findings and nothing
     * else: coverage is this project's own reporting and Windrow has none yet,
     * so a corpus that demanded it would be unanswerable on the other side.
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
