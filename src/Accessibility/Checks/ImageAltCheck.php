<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMXPath;

/**
 * An image with no text alternative.
 *
 * WCAG 1.1.1, and the narrowest family in the pack: one rule. Alt text that
 * exists but is poor (a filename, a caption repeated word for word) is not
 * judged here, because a static pass cannot tell a good description from a bad
 * one and a gate that guessed would be refusing publishes on an opinion.
 */
final class ImageAltCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.media.alt_missing';
    }

    public static function rules(): array
    {
        return ['image-missing-alt'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $violations = [];

        foreach ($xpath->query('//img') as $img) {
            // A decorative image opts out of a text alternative explicitly:
            // alt="" plus role="presentation"/"none", or aria-hidden. A
            // meaningful image needs a non-empty alt, so a missing attribute and
            // an empty one that is not marked decorative both leave it without an
            // accessible name.
            $role = strtolower($img->getAttribute('role'));
            $decorative = in_array($role, ['presentation', 'none'], true)
                || $img->getAttribute('aria-hidden') === 'true';

            if (! $img->hasAttribute('alt') || (! $decorative && trim($img->getAttribute('alt')) === '')) {
                $violations[] = $this->issue(
                    'image-missing-alt',
                    Violation::ERROR,
                    $img->getAttribute('src'),
                );
            }
        }

        return $violations;
    }
}
