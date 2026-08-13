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
            // Two different things wear the same symptom, and the addon reported
            // one as the other until a stock Statamic install showed it. A
            // collection the site never routes has no page at all. A collection
            // that routes on the page tree gives an entry no URL until it is in
            // that tree, which it is not while `EntrySaving` is running, so the
            // first save of a page is unchecked and every save after it is not.
            throw ($entry->collection()?->routes()->filter()->isNotEmpty() ?? false)
                ? new PageHasNoAddressYet('the page has no address yet')
                : new EntryHasNoPage('the entry has no URL');
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

        // The same problem one layer along, and it took a scheduled post to find
        // it. A collection can be set so that entries dated in the future (or in
        // the past) are private, and `DataResponse::handlePrivateEntries()` throws
        // the same 404 for those as it does for a draft. Publishing was not the
        // only thing standing between the gate and a page.
        //
        // That made every scheduled post unpublishable while the gate refuses:
        // the checks could not run, the gate does not read that as a pass, and so
        // the save was refused. An accessibility tool that stops a site scheduling
        // posts is worse than no accessibility tool.
        //
        // Dates are restored in `finally` exactly like the published flag.
        $wasDate = $entry->collection()?->dated() ? $entry->date() : null;

        try {
            if (! $wasPublished) {
                $entry->published(true);
            }

            // Nudged to a date the collection is willing to show. Now first,
            // which answers the common case of a post scheduled ahead. A
            // collection that hides past entries instead needs the opposite, and
            // one configured to hide both has no visible date at all: the loop
            // stops trying and the 404 is reported honestly rather than papered
            // over with a date that changes nothing.
            if ($wasDate !== null && $entry->private()) {
                foreach ([now(), now()->addYear()] as $visible) {
                    $entry->date($visible);

                    if (! $entry->private()) {
                        break;
                    }
                }
            }

            // Built after the date is settled, not before. A collection that
            // routes on the date ("/{year}/{month}/{slug}") gives a different URL
            // once the date moves, and requesting the old one would 404 on a page
            // that was about to render perfectly well.
            $request = Request::create($entry->absoluteUrl() ?: $url, 'GET');

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

            // An entry left holding today's date by a check would be saved with
            // it by the next save that touched it, silently unscheduling a post
            // nobody asked to unschedule. Same reasoning as the published flag,
            // and the same reason both are restored here rather than after the
            // status checks below, which throw.
            if ($wasDate !== null) {
                $entry->date($wasDate);
            }

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
