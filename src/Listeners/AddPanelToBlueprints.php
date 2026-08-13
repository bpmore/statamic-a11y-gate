<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Listeners;

use Bpmore\A11yGate\Gate\GateSettings;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Facades\Collection;

/**
 * Puts the accessibility panel in every gated collection's sidebar, so nobody
 * has to edit seven blueprint files by hand.
 *
 * The mechanism is Statamic's own: `slug` and `date` are not in your yaml
 * either, they are added to the blueprint as it is found. This uses the same
 * call, which means the field behaves like a native one and disappears cleanly
 * the day the addon is removed, leaving no orphaned handle in a file somebody
 * has to go and delete.
 *
 * **Off unless the site asks.** An addon that rearranges publish forms on
 * install is an addon that gets uninstalled by somebody who did not choose it,
 * and a field appearing in a form nobody added it to is the kind of surprise
 * that makes people distrust everything else it does.
 */
final class AddPanelToBlueprints
{
    public function __construct(private readonly GateSettings $settings) {}

    public function handle(EntryBlueprintFound $event): void
    {
        if (! $this->settings->addsPanel) {
            return;
        }

        // The collection comes from the blueprint's own namespace rather than
        // from `$event->entry`, which is null whenever a blueprint is found
        // without one: creating an entry, editing the blueprint itself, or any
        // listing screen.
        $namespace = $event->blueprint->namespace();

        if (! is_string($namespace) || ! str_starts_with($namespace, 'collections.')) {
            return;
        }

        $handle = substr($namespace, strlen('collections.'));

        if (! $this->settings->gates($handle)) {
            return;
        }

        // A collection with no route has no pages, so there would be nothing for
        // the panel to check and it would sit in the form saying so forever.
        $collection = Collection::findByHandle($handle);

        if (! $collection || $collection->routes()->filter()->isEmpty()) {
            return;
        }

        // `ensureField` is by handle, so a site that already added this to a
        // blueprint keeps its own placement and does not get a second copy.
        $event->blueprint->ensureField(
            'a11y_panel',
            ['type' => 'accessibility_panel', 'display' => 'Accessibility'],
            'sidebar',
        );
    }
}
