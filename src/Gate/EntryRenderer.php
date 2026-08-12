<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Gate;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Statamic\Contracts\Entries\Entry;
use Statamic\Facades\Stache;
use Throwable;

/**
 * The unsaved entry, rendered through the site's own templates.
 *
 * The addon does not know how to turn an entry into a page and must not learn:
 * the site's templates are the only thing that knows what its pages look like,
 * and a checker running against markup the addon invented would be checking the
 * wrong document.
 *
 * The two lines that matter were found by running them against a real Statamic 6
 * site, not by reading documentation, and the reasoning is in the decision log
 * under the render spike.
 *
 * `substitute()` is the one that is easy to leave out and impossible to notice
 * missing: `toResponse()` re-resolves the entry during rendering rather than
 * trusting the instance it was called on, so without it the page comes back
 * showing the SAVED values and the gate passes changes it never saw. It is
 * lifted from Statamic's own Live Preview token handler, which does exactly this
 * for the same reason.
 */
final class EntryRenderer
{
    public function __construct(private readonly Application $app) {}

    /**
     * @throws CouldNotRender when there is no page, or the page did not render
     */
    public function render(Entry $entry): string
    {
        $url = $entry->absoluteUrl();

        if (! is_string($url) || $url === '') {
            throw new EntryHasNoPage('the entry has no URL');
        }

        // An entry being created for the first time has no id yet, and
        // `substitute()` indexes by id: it fatals on null. Statamic assigns one
        // the same way moments later, in `EntryRepository::save()`, so this is
        // the value the entry was going to get anyway rather than an invention.
        //
        // If the gate then refuses, the entry keeps an id and is never written.
        // Nothing reads it: the instance is discarded with the failed request.
        if (! $entry->id()) {
            $entry->id(Stache::generateId());
        }

        // Register this unsaved instance with its repository, so lookups during
        // rendering return it rather than whatever is on disk.
        $entry->repository()->substitute($entry);

        $request = Request::create($url, 'GET');
        $previous = $this->app->bound('request') ? $this->app->make('request') : null;

        try {
            // The current request during a save is the control panel's PATCH.
            // Templates that ask for the request would otherwise render the page
            // as though the visitor were at a control-panel URL, so the front-end
            // request is bound for the duration of the render and put back after.
            $this->app->instance('request', $request);

            $response = $entry->toResponse($request);
            $status = $response->getStatusCode();
            $html = (string) $response->getContent();
        } catch (Throwable $e) {
            throw new CouldNotRender('the page threw while rendering: '.$e->getMessage(), previous: $e);
        } finally {
            if ($previous !== null) {
                $this->app->instance('request', $previous);
            }
        }

        if ($status !== 200) {
            throw new CouldNotRender("the page came back as HTTP {$status}");
        }

        if (trim($html) === '') {
            throw new CouldNotRender('the page came back empty');
        }

        return $html;
    }
}
