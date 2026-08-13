<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

/**
 * The collection routes its entries, but this one has no URL yet.
 *
 * Found by installing the addon on a stock Statamic site rather than by any test
 * here. The default `pages` collection routes on `{parent_uri}/{slug}`, and that
 * URI comes from the page tree. An entry being created is not in the tree when
 * `EntrySaving` fires, so it has no URL, and there is nothing to fetch and check.
 *
 * **So the first save of a page in a structured collection is not checked**, and
 * every save after it is. That is a real hole and it is deliberate rather than
 * hidden: refusing here would mean no page could ever be created in such a
 * collection, which is worse than one unchecked save.
 *
 * A separate type from `EntryHasNoPage` because they mean different things and
 * the addon was reporting one as the other. "The entry has no page of its own"
 * is true of a collection the site never routes and false here: this entry has a
 * page, it just does not have an address yet. Saying the false one is the
 * failure this product exists to refuse, in miniature.
 *
 * Extends `EntryHasNoPage` so it keeps the same answer to the only question the
 * gate asks of it, which is whether to refuse. It does not.
 */
final class PageHasNoAddressYet extends EntryHasNoPage {}
