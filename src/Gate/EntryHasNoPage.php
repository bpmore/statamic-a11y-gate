<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

/**
 * The entry has no URL, so there is no page of its own to check.
 *
 * A separate type from its parent because the two mean opposite things to the
 * gate. A page that failed to render is a check that could not run and must fail
 * closed. An entry with no page at all is nothing to check, and refusing to save
 * one would block every entry in a collection the site never routes.
 *
 * Distinguished by type rather than by reading the message, so a reworded string
 * cannot quietly turn one into the other.
 *
 * Not final, and that is the only reason it is not. `PageHasNoAddressYet` extends
 * it so that a page which simply has no URL yet inherits the one answer that
 * matters here, which is that the gate does not refuse it. Anything else
 * extending this is a mistake: a subclass that ought to fail closed belongs under
 * `CouldNotRender` instead.
 */
class EntryHasNoPage extends CouldNotRender {}
