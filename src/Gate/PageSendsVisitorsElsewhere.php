<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

/**
 * The page answered with a redirect, so there was no page to check.
 *
 * A page for signed-in visitors opens with `{{ redirect }}` when nobody is, and
 * the gate is nobody. Seen on a learning site's account page, which the gate
 * refused on every save as a check that could not run. Nothing had failed: the
 * page did exactly what it was written to do, and the author had nothing to fix.
 *
 * Extends `EntryHasNoPage` for the one answer that matters, which is that the
 * gate does not refuse. It is its own type because it means something different
 * and has to be said differently: this entry has a page, and somebody who is
 * signed in sees it. **That page has not been checked**, and everything that
 * reports this must say so rather than fold it into the clean total.
 *
 * Only a redirect that leaves the page counts. One that points back at the
 * page's own address is a loop nobody will ever get through, which is a
 * template fault, and that stays under `CouldNotRender` where it fails closed.
 */
final class PageSendsVisitorsElsewhere extends EntryHasNoPage
{
    public function __construct(public readonly string $location, public readonly int $status)
    {
        parent::__construct("the page sends visitors to {$location} (HTTP {$status})");
    }
}
