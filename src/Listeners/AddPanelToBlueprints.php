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
 * **On unless the site says otherwise**, which is the reverse of how this
 * shipped at first. Rearranging somebody's publish form uninvited is a real
 * cost and it was the one this weighed. The larger one was missed: the gate
 * refuses a publish on every collection from the moment the addon is installed,
 * the findings come back with the refusal, and this panel is the only thing that
 * draws them. Off, an author is stopped and shown Statamic's own "The given data
 * was invalid" in the corner, with nowhere to look.
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

        // The collection and blueprint are stamped into the field's own config,
        // and that is the only way the panel can check a page before its first
        // save. Statamic's create screen sends no entry reference, because its
        // view data has none, and it never sets the blueprint's parent either,
        // so a fieldtype has nothing to ask. Field config does reach the browser
        // (`Field::toPublishArray()` merges the raw config), so the answer is
        // put there by the one piece of code that already knows it.
        //
        // Without this an author on a new page is told to save it first, and on
        // a collection that publishes by default there is no way to save without
        // publishing, and the gate refuses the publish. Told to do the one thing
        // the gate will not let them do.
        //
        // `ensureField` is by handle, so a site that already added this to a
        // blueprint keeps its own placement and does not get a second copy. It
        // also keeps its own config, which means a hand-placed field has no
        // collection stamped on it and falls back to asking for a save. That is
        // the honest outcome rather than this addon guessing.
        $event->blueprint->ensureField(
            'a11y_panel',
            [
                'type' => 'accessibility_panel',
                'display' => 'Accessibility',
                'collection' => $handle,
                'blueprint' => $event->blueprint->handle(),
            ],
            'sidebar',
        );
    }
}
