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

    public static function needsOptIn(): bool
    {
        return true;
    }

    /**
     * Full where the site stamps the attribute. Nothing to report at all on a
     * site that has not integrated this, and `none` on a site that has but did
     * not stamp this page.
     *
     * The middle case is the one worth having: a site that says it marks up its
     * unpublished links, on a page where nothing was marked, has a real gap. A
     * site that never opted in does not need telling on every entry forever, and
     * the config file names this check instead.
     */
    public function coverage(DOMXPath $xpath, array $optedIn = []): ?Coverage
    {
        $stamped = $xpath->query('//*[@data-windrow-unpublished-link]');

        if ($stamped !== false && $stamped->length > 0) {
            return Coverage::full(self::key(), self::name());
        }

        if (! in_array(self::key(), $optedIn, true)) {
            return null;
        }

        return Coverage::none(
            self::key(),
            self::name(),
            'A link to an unpublished page looks like any other link once the page is built, and nothing on this page was marked up as one.',
        );
    }
}
