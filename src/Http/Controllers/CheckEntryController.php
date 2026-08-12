<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Http\Controllers;

use Bpmore\A11yGate\Accessibility\Violation;
use Bpmore\A11yGate\Gate\GateResult;
use Bpmore\A11yGate\Gate\PublishGate;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Data;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Support\Arr;

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
        $entry = Data::find((string) $request->input('reference'));

        abort_if(! $entry instanceof Entry, 404);

        // The same permission Statamic requires to open the entry. Rendering a
        // page is cheap to ask for and this endpoint takes arbitrary values, so
        // it must not be a way to render an entry somebody cannot already read.
        $this->authorize('view', $entry);

        // Lifted from Statamic's PreviewController: process the submitted values
        // through the blueprint, then attach them as supplements. `setSupplement`
        // is what augmentation reads, and it is what Live Preview uses, so this
        // is the supported shape rather than a trick.
        $fields = $entry->blueprint()->fields()
            ->addValues((array) $request->input('values', []))
            ->process();

        foreach (Arr::except($fields->values()->all(), ['slug']) as $key => $value) {
            $entry->setSupplement($key, $value);
        }

        return $this->present($gate->examine($entry));
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

            // Sent whether or not anything was found, and the panel shows it
            // whether or not anything was found. A clean result is the case
            // where "how much of this was actually looked at" matters most.
            'coverage_summary' => $result->coverageSummary,
            'coverage' => array_map(fn ($c) => $c->toArray(), $result->coverage),

            // Sent from the server rather than written into the component, so
            // that the sentence a customer reads and the sentence this project
            // is willing to defend are the same string. Nothing here says the
            // page is accessible, compliant, or a proportion of either.
            'limits' => 'This reads the rendered page. It cannot tell a good description from a bad one, and a page it finds nothing wrong with has not been proven accessible.',
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
