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
     * @return array<int, Violation>
     */
    public function check(string $html, ?AccessibilityStandard $standard = null): array
    {
        $xpath = $this->xpath($html);

        $violations = [];

        foreach (CheckPack::all() as $check) {
            $violations = [...$violations, ...$check->run($xpath, $standard)];
        }

        return $violations;
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
