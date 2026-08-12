<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

use Bpmore\A11yGate\Accessibility\AccessibilityStandard;
use Bpmore\A11yGate\Accessibility\Coverage;
use Bpmore\A11yGate\Accessibility\Violation;
use DOMXPath;

/**
 * A link to a page that is not live yet.
 *
 * Only the renderer can resolve a page reference. By the time the HTML exists,
 * a link to an unpublished page looks like any other link, so this rule reads an
 * attribute the host has to stamp and finds nothing without it.
 *
 * A warning, not a refusal. A draft link is a normal state during authoring, and
 * a site that cannot publish a page because a page it links to is not published
 * yet has an ordering problem with no exit.
 */
final class InternalLinkCheck extends RuleCheck
{
    public static function key(): string
    {
        return 'a11y.link.unpublished';
    }

    public static function name(): string
    {
        return 'Links to unpublished pages';
    }

    public static function rules(): array
    {
        return ['link-unpublished-page'];
    }

    /**
     * @return array<int, Violation>
     */
    public function run(DOMXPath $xpath, ?AccessibilityStandard $standard = null): array
    {
        $violations = [];

        foreach ($xpath->query('//*[@data-windrow-unpublished-link="true"]') as $node) {
            $violations[] = $this->issue('link-unpublished-page', Violation::WARN);
        }

        return $violations;
    }

    /**
     * Full only where the site stamps the attribute, and nothing at all
     * otherwise. Once the HTML exists, a link to a draft looks like any other
     * link, so with no stamp there is no question this rule can answer.
     */
    public function coverage(DOMXPath $xpath): Coverage
    {
        $stamped = $xpath->query('//*[@data-windrow-unpublished-link]');

        return $stamped !== false && $stamped->length > 0
            ? Coverage::full(self::key(), self::name())
            : Coverage::none(
                self::key(),
                self::name(),
                'A link to an unpublished page looks like any other link once the page is built, so this is only checked on sites that mark those links up.',
            );
    }
}
