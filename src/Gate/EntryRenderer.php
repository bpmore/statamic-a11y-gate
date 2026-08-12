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
 * `substitute()` registers this instance with its repository, so that lookups
 * during rendering return it rather than whatever is on disk. It is lifted from
 * Statamic's own Live Preview token handler, which does exactly this.
 *
 * **Whether it is load-bearing is honestly unknown, and it stays.** Removing it
 * changes nothing in the test suite, and nothing on a real Statamic 6 site with
 * a file-backed Stache either: both were tried, and the unsaved values rendered
 * anyway, because the repository hands back the same in-memory instance it was
 * given. What it protects against is a repository that does not: a different
 * driver, a cleared cache, a lookup by URI rather than by id. The line is one
 * call, the failure it would prevent is the gate silently checking the old
 * version of the page, and that failure is invisible when it happens. Keeping an
 * unproven line is the cheaper mistake here, and saying so is better than
 * implying it was measured.
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

        // A draft has no public page: `DataResponse::handleDraft()` throws a 404
        // unless the request carries a Live Preview token for this exact entry.
        // Found by watching the panel report "could not check" on a draft, which
        // is the state an author most wants checked: before it goes live.
        //
        // Statamic's answer is a token, and this is not one. A token means a
        // write to the token store and a stored copy of the entry, both of which
        // exist so a *browser* can request the front end. This renders in the
        // same process, so the published flag on the in-memory instance is
        // flipped for the length of the render and put back afterwards. Nothing
        // is saved, nothing is cached, and there is no token to clean up.
        $wasPublished = $entry->published();

        try {
            if (! $wasPublished) {
                $entry->published(true);
            }

            // The current request during a save is the control panel's PATCH.
            // Templates that ask for the request would otherwise render the page
            // as though the visitor were at a control-panel URL, so the front-end
            // request is bound for the duration of the render and put back after.
            $this->app->instance('request', $request);

            $response = $entry->toResponse($request);
            $status = $response->getStatusCode();
            $html = (string) $response->getContent();
        } catch (Throwable $e) {
            // The class, not just the message. Statamic's NotFoundHttpException
            // carries no message at all, so reporting the message alone produced
            // "the page threw while rendering: ." on screen, which told an author
            // nothing and told whoever had to debug it less.
            //
            // Not covered by a test, and that is a gap rather than an oversight:
            // the test harness renders an undefined tag to an empty string rather
            // than throwing, so nothing in it produces a message-less throw. This
            // was seen on a real control panel and fixed from there.
            $reason = $e->getMessage() !== '' ? $e->getMessage() : $e::class;

            throw new CouldNotRender('the page threw while rendering: '.$reason, previous: $e);
        } finally {
            $entry->published($wasPublished);

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
