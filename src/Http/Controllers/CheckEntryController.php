<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Http\Controllers;

use Bpmore\A11yGate\Accessibility\Violation;
use Bpmore\A11yGate\Gate\GateResult;
use Bpmore\A11yGate\Gate\PublishGate;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Fields\Blueprint;
use Statamic\Facades\Collection;
use Statamic\Facades\Data;
use Statamic\Facades\Entry;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Support\Arr;
use Statamic\Support\Str;

/**
 * What the panel asks: check this entry as it stands in the form right now.
 *
 * The values arrive unsaved, and applying them the way Statamic's own Live
 * Preview does is the whole reason this is a controller rather than a read of
 * the file on disk. A panel that checked the saved version would be at its most
 * wrong exactly when it matters: after an author has broken the page and before
 * they press publish.
 */
class CheckEntryController extends CpController
{
    public function __invoke(Request $request, PublishGate $gate)
    {
        // A reference ("entry::abc123") rather than a bare id, because that is
        // what the publish container already holds and hands to the panel. No
        // parsing in the browser, and no second format to keep in step.
        $reference = (string) $request->input('reference');

        // Every failure below says why, in a sentence, and that is not politeness.
        // These used to be bare `abort()` calls, which Laravel answers with
        // `{"message": ""}`. The panel read that empty string as "no message" in
        // one place and as "no failure" in another, so a check that could not run
        // drew nothing at all: same screen as a page with nothing wrong with it.
        // The one mistake this product is not allowed to make.
        //
        // An entry being created for the first time has no reference to send.
        // Statamic's create screen has no `reference` in its view data at all
        // (`EntriesController::create()`), only its edit screen does, so this is
        // the shape of every check pressed before the first save rather than a
        // rare edge, and it is answered by building the entry rather than by
        // asking the author to save first. On a collection that publishes by
        // default there is no way to save without publishing, and the gate
        // refuses the publish, so "save it first" was advice nobody could take.
        return $this->present($gate->examine(
            $reference === ''
                ? $this->entryBeingCreated($request)
                : $this->entryBeingEdited($request, $reference)
        ));
    }

    /**
     * An entry that exists, with the form's unsaved values laid over it.
     */
    private function entryBeingEdited(Request $request, string $reference): EntryContract
    {
        $entry = Data::find($reference);

        abort_if(
            ! $entry instanceof EntryContract,
            404,
            'That entry could not be found, so nothing about it was checked.'
        );

        // The same permission Statamic requires to open the entry. Rendering a
        // page is cheap to ask for and this endpoint takes arbitrary values, so
        // it must not be a way to render an entry somebody cannot already read.
        $this->authorize('view', $entry);

        // Lifted from Statamic's PreviewController: process the submitted values
        // through the blueprint, then attach them as supplements. `setSupplement`
        // is what augmentation reads, and it is what Live Preview uses, so this
        // is the supported shape rather than a trick.
        foreach (Arr::except($this->submitted($request, $entry->blueprint()), ['slug']) as $key => $value) {
            $entry->setSupplement($key, $value);
        }

        return $entry;
    }

    /**
     * An entry that does not exist yet, built from the form the author is
     * looking at. Nothing is saved: the instance is discarded with the request.
     *
     * The collection and blueprint come from the panel's own field config,
     * stamped there by the listener that places the field. See
     * `AddPanelToBlueprints` for why that is the only place they can come from.
     */
    private function entryBeingCreated(Request $request): EntryContract
    {
        $handle = (string) $request->input('collection');

        // A field somebody placed in a blueprint by hand carries no collection,
        // because `ensureField` leaves an existing field's config alone. Saying
        // so is better than guessing which collection they meant.
        abort_if(
            $handle === '',
            422,
            'This page has not been saved yet, and the panel was not told which collection it belongs to. Save it once, then check it.'
        );

        $collection = Collection::findByHandle($handle);

        abort_if(
            ! $collection,
            404,
            "The collection [{$handle}] could not be found, so nothing was checked."
        );

        // `create` rather than `view`: there is no entry to view yet, and this
        // renders a page from values the caller supplies, so it must not be a
        // way into a collection somebody has no business writing to.
        $this->authorize('create', [EntryContract::class, $collection]);

        // Null and not an empty string when the panel did not name one, which is
        // the difference between "give me the collection's default form" and
        // "find the form called nothing". The second answers null and 404s.
        $wanted = (string) $request->input('blueprint');

        $blueprint = $collection->entryBlueprint($wanted !== '' ? $wanted : null);

        abort_if(
            ! $blueprint,
            404,
            'That form could not be found, so nothing was checked.'
        );

        $values = $this->submitted($request, $blueprint);

        $entry = Entry::make()->collection($collection);

        // A dated collection routes on the date, and an entry with none has no
        // URL at all. Now is what Statamic's own create screen would have used.
        if ($collection->dated()) {
            $entry->date(now());
        }

        // The slug is what the URL is built from, so it is set for real rather
        // than supplemented.
        $slug = (string) ($values['slug'] ?? Str::slug((string) ($values['title'] ?? '')));

        // An author who has not named the page yet has no slug. Left unset that
        // is not an absent URL, it is the collection's route with a hole in it,
        // which renders whatever happens to live there and reports back about
        // somebody else's page. Refused outright instead, and said in the words
        // an author can act on.
        abort_if(
            $slug === '',
            422,
            'This page has no title yet, so there is no page to check. Give it a title, then check it.'
        );

        $entry->slug($slug);

        $entry->data(Arr::except($values, ['slug']) + ['blueprint' => $blueprint->handle()]);

        return $entry;
    }

    /**
     * The form's values, run through the blueprint the way a save would.
     *
     * @return array<string, mixed>
     */
    private function submitted(Request $request, Blueprint $blueprint): array
    {
        return $blueprint->fields()
            ->addValues((array) $request->input('values', []))
            ->process()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(GateResult $result): array
    {
        return [
            'outcome' => $result->outcome,
            'reason' => $result->reason,
            'refuses' => $result->shouldRefuse(),
            'errors' => array_map($this->finding(...), $result->errors()),
            'warnings' => array_map($this->finding(...), $result->warnings()),

            // What the panel shows: gaps in the author's own content that only
            // they can settle.
            'notices' => $result->notices,

            // What the panel does not show, and keeps sending anyway. Which
            // checks ran and how far is what somebody auditing this addon needs,
            // and it is meaningless to the person writing the page. Kept in the
            // response so a report can be built on it without a second endpoint.
            'coverage_summary' => $result->coverageSummary,
            'coverage' => array_map(fn ($c) => $c->toArray(), $result->coverage),

        ];
    }

    /**
     * @return array<string, string>
     */
    private function finding(Violation $violation): array
    {
        return [
            'message' => $violation->message,
            'cta' => $violation->cta,
            'pointer' => $violation->pointer,
            'label' => $violation->wcag,
        ];
    }
}
