<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
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

    public static function name(): string
    {
        return 'Image descriptions';
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
            // role="presentation"/"none" or aria-hidden, with or without an
            // alt attribute. The marker alone used to be refused when alt was
            // missing entirely, on the argument that alt="" is what shows the
            // omission was a decision. That refused markup assistive tech
            // treats as decorative (axe passes it), while citing WCAG 1.1.1,
            // which such an image does not fail. Citing a criterion the check
            // cannot establish is the one mistake this product is built to
            // avoid, so the marker now wins either way.
            $role = strtolower($img->getAttribute('role'));
            $decorative = in_array($role, ['presentation', 'none'], true)
                || $img->getAttribute('aria-hidden') === 'true';

            if ($decorative) {
                continue;
            }

            // A meaningful image needs a non-empty alt: a missing attribute and
            // an empty one both leave it without an accessible name.
            if (trim($img->getAttribute('alt')) === '') {
                $violations[] = $this->issue(
                    'image-missing-alt',
                    $img->getAttribute('src'),
                );
            }
        }

        return $violations;
    }

    /**
     * Always full for what it claims to judge, which is whether a description
     * exists. Whether an existing description is any good is a different rule
     * that this checker does not have and does not pretend to.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        return Coverage::full(self::key(), self::name());
    }
}
