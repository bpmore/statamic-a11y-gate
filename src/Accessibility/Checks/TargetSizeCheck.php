<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMElement;
use DOMXPath;

/**
 * Controls smaller than the standard's minimum touch target.
 *
 * WCAG 2.5.8 at AA, 2.5.5 at AAA, which is why this is the one family that reads
 * the resolved standard rather than a constant.
 *
 * **It can only see inline styles.** A size set in a stylesheet is invisible to
 * a pass over HTML, and most sites size their buttons in a stylesheet, so on a
 * typical page this rule finds nothing and that is not a pass. A browser pass
 * with axe measures the computed box and catches what this cannot. The addon
 * says so rather than letting a quiet result imply otherwise.
 *
 * Known over-reach, accepted rather than hidden: 2.5.8 also passes an undersized
 * target that has enough clear space around it, and no static pass can measure
 * spacing. So this can fire on markup that conforms through the spacing
 * exception. The remedy it asks for (make the control at least 24x24) is always
 * a valid way to conform and never makes the page worse, and staying quiet about
 * a control we can see is too small is the worse failure for a gate.
 */
final class TargetSizeCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.target.size';
    }

    public static function rules(): array
    {
        return ['target-size-minimum'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $min = $standard?->targetSize ?? AccessibilityStandard::wcag22aa()->targetSize;
        $violations = [];

        // Each alternative needs its own `//`: a bare `*[@role=...]` step is
        // relative to the document node and only ever matches the root element.
        $selector = '//a|//button|//*[@role="button"]|//*[@role="link"]';

        foreach ($xpath->query($selector) as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }

            $style = $el->getAttribute('style');

            if ($style === '' || ! $this->inlineSizeBelow($style, $min)) {
                continue;
            }

            $violations[] = $this->issue(
                'target-size-minimum',
                Violation::ERROR,
                $this->snippet($el->textContent ?: $el->getAttribute('aria-label')),
            );
        }

        return $violations;
    }

    /**
     * True when an inline style sets width, height or either minimum below $min
     * CSS pixels.
     */
    private function inlineSizeBelow(string $style, int $min): bool
    {
        if (! preg_match_all(
            '/(?:^|;)\s*(?:min-)?(?:width|height)\s*:\s*(\d+(?:\.\d+)?)\s*px/i',
            $style,
            $matches,
        )) {
            return false;
        }

        foreach ($matches[1] as $value) {
            if ((float) $value < $min) {
                return true;
            }
        }

        return false;
    }
}
