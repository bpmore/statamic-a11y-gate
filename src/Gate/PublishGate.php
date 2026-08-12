<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Statamic\Contracts\Entries\Entry;

/**
 * Decides what the gate knows about an entry that is about to be saved.
 *
 * It decides nothing about what to do with that: refusing, warning and wording
 * the refusal all live in the listener. This class answers one question, and it
 * is the question a test can ask without an event, a request or a save.
 *
 * **What counts as publishing.** Statamic has no publishing event: the entry
 * events are Creating, Saving, Saved, Created, Deleting, Deleted and
 * ScheduleReached, checked against `statamic/cms v6.27.1`. So the addon decides
 * for itself, and it decides on the state the save would leave behind: an entry
 * that will be live when this save finishes is checked, and a draft is not.
 *
 * That covers the publish action as well as the save button, verified in source:
 * `Publishable::publish()` sets `published(true)` and calls `save()`, and the
 * revisions path does the same through `publishWorkingCopy()`. Both dispatch
 * `EntrySaving`, so both arrive here.
 *
 * It also means re-saving an already published entry is checked, which is
 * deliberate. A live page that a later edit breaks is exactly as broken as one
 * published broken.
 */
final class PublishGate
{
    public function __construct(
        private readonly EntryRenderer $renderer,
        private readonly StaticAccessibilityChecker $checker,
        private readonly GateSettings $settings,
    ) {}

    public function inspect(Entry $entry): GateResult
    {
        if (! $entry->published()) {
            return GateResult::notApplicable('the entry is a draft');
        }

        $collection = $entry->collection()?->handle();

        if ($collection === null || ! $this->settings->gates($collection)) {
            return GateResult::notApplicable("the collection [{$collection}] is not gated");
        }

        try {
            $html = $this->renderer->render($entry);
        } catch (EntryHasNoPage) {
            // Nothing to check rather than a failure to check, and the order of
            // these two catches is the whole distinction: an entry the site
            // never routes has no page to get wrong.
            return GateResult::notApplicable('the entry has no page of its own');
        } catch (CouldNotRender $e) {
            // The page exists and did not come back. The gate must not read that
            // as a clean result.
            return GateResult::couldNotCheck($e->getMessage());
        }

        return GateResult::checked($this->checker->check($html, $this->settings->standard));
    }
}
