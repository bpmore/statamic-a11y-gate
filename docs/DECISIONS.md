# Decision log

The **why**, and the **alternatives that were rejected**, behind choices that are
not obvious from the code they produced.

Newest first, headed `## YYYY-MM-DD: what was settled`, with a `---` between
entries.

Entries are not edited when they stop being true. A decision that was reversed
gets a newer entry above it saying so, because why something changed is worth
more than a file that only ever describes the present.

---

## 2026-08-13: The panel is on by default, because the gate is

Reverses the entry lower down that made it off by default. That entry is right
about its own argument and was answering the wrong question.

Its argument: an addon that rearranges publish forms on install is one that gets
uninstalled by somebody who did not choose it, and a field appearing in a form
nobody added it to is a surprise that makes people distrust everything else it
does. All true, and none of it was weighed against what the other defaults do.

**The two defaults contradicted each other.** The gate ships on for every
collection, in refuse mode, which is maximum intervention. The panel shipped off,
which is minimum intervention. So a fresh install stopped somebody publishing and
gave them nowhere to look.

The findings are not missing. They come back with the refusal, on the publish
container, under the listener's own key. But Statamic renders errors next to the
field they name, `a11y_gate` names no field, and this panel is the only thing
that draws them. Without it the entire result is Statamic's own "The given data
was invalid" in the bottom-left corner, which is its wording for every validation
failure in the control panel and not ours to change.

That was observed rather than reasoned about. It is exactly what an author saw
this afternoon before the panel learned to draw a refusal, with the panel
switched on. Off, it is strictly worse.

**Rejected: defaulting to warn mode instead.** It makes the install polite by
turning the gate into a reporter, and refusing is the product. `CLAUDE.md`
requires stopping and asking before any change that turns a refusal into a
warning, so it was asked rather than assumed, and turned down.

Being coy about a read-only sidebar panel while blocking publishing on every
collection was not caution. It was inconsistency, and the panel was the cheaper
half to fix.

Switching the panel off is still supported and still reasonable for a site that
places the field by hand. The config comment now says plainly what it costs: a
refusal is unreadable unless that field is somewhere.

---

## 2026-08-13: The addon stands on its own, and the corpus stays anyway

Reverses the entry below it, which is four hours old. That one kept the
old stamped attribute names on the grounds that renaming them meant a
coordinated corpus change across two repositories. There is no second repository
in this arrangement any more, so the reason evaporated rather than being argued
down.

**What was separated.** The shared corpus arrangement ended. The stamped
attributes are `data-a11y-*`. `CLAUDE.md` lost the sections about porting rules
verbatim and keeping two copies in step. Around twenty source comments that
explained what this fork narrowed or copied byte for byte now say what the code
does, with nothing to compare it against.

**What was nearly thrown away with it, and was not.** The question was framed as
losing the corpus and replacing it with a written spec for keeping two products
aligned. That framing was wrong, and saying so was more useful than doing as
asked: the corpus's stated purpose was stopping two implementations disagreeing,
but its day to day value is that it is the strongest regression test here, and
that value never depended on anyone else running it. It caught a fix
over-reaching twice in one afternoon, in cases written minutes apart. It stayed.

A spec was rejected as a replacement on its own terms: a corpus is executable and
fails a build, a spec is prose nobody runs. That difference only matters if two
things must still agree, and after this they do not.

**The consistency guarantee is what was actually given up**, and it was worth
less than it sounded. Its justification was that "did this page pass?" must not
depend on which implementation ran. That bites only if one customer could
plausibly use both, and nobody could name that customer.

**What replaced it is nothing, and that is the risk.** Two copies drifting was a
danger something outside this repository would eventually notice. Now nothing
will. The corpus is the only thing that stands between a rule changing its mind
and nobody finding out, which is why it is still the first rule in `CLAUDE.md`
that is not about the product.

**The attribute rename is a breaking change**, and the release notes have to lead
with it rather than bury it. A site already stamping the old names stops being
checked by six rules, and stops silently. Nothing found is not the same as
nothing wrong, and that is the one failure this addon exists to refuse.

**The decision log lost the name too.** It was kept at first, on the reasoning
that this file is a record rather than a pitch and a record with the reasons
filed off is worth less than no record. That was overruled, and the overruling
was right: the log is public, it named the other product twenty-nine times, and
it was the one file that let a reader undo the whole separation in about a
minute. Everything that made the reasoning worth having survived the rename. The
entries still say what was ported, what was narrowed, and what was given up. They
say it about "upstream" instead, which costs a reader nothing they can act on.

---

## 2026-08-13: The name comes out of the README and stays in the attributes

Two decisions that look like the same one and are not.

The README named the other product twice, against the rule in `CLAUDE.md` that
nothing here carries that name and that somebody evaluating this addon should not
need to know what it is. Both mentions went. Neither was doing any work: one
offered a consistency guarantee the reader cannot check, about a product they
have never heard of, and the other pointed at a design document in a repository
they cannot open.

**The stamped attributes keep the other product's prefix, and that is the harder call.** They are a
worse version of the same problem: `config/statamic-a11y-gate.php` documents them
as the markup a host adds to their own templates to switch on the two opt-in
checks, so a site owner has to type the name rather than merely read it. That is
squarely what the rule exists to prevent.

They stay anyway, for reasons that outweigh it:

- **They are the shared contract, not a label.** The corpus fixtures carry these
  attributes, and both projects are held to producing the same findings from the
  same markup. Renaming them here changes what markup this checker detects, which
  is a behaviour change, which means a corpus commit in both repositories.
- **The other side would have to change too**, in its templates, to keep emitting
  markup this fork still reads. A rename that is only half done leaves the two
  projects silently disagreeing about a page, which is the exact failure the
  corpus exists to prevent.
- **The cost is bounded and small.** Two optional checks, off by default,
  configured by a developer rather than an author. Nobody who installs this addon
  and uses it as intended ever sees the string.

An alias, reading both the old and a new name, was considered and rejected. It
doubles the surface of the one thing the two projects must agree on, to hide a
name in a config comment.

If this is revisited, the order matters: corpus first, in both repositories, then
both implementations, then the templates that emit it. Doing it in any other
order breaks the guarantee while the work is half finished.

---

## 2026-08-13: The refusal is drawn in the panel, because the toast cannot say it

An author who pressed Save & Publish on a page the gate refuses got a red toast
in the bottom-left corner reading "The given data was invalid." Nothing about
accessibility, nothing about what to fix, in the corner furthest from the form.

Two facts, both read in vendor source rather than guessed:

- **The position is Statamic's.** Its toast plugin is configured
  `position: "bottom-left"` in the control panel bundle.
- **The words are Statamic's, and they are hard-coded.**
  `Statamic\Exceptions\ValidationException::summarize()` overrides Laravel's and
  returns "The given data was invalid." for every validation failure in the
  control panel. Laravel would have used the first error message. Statamic throws
  it away.

So the toast cannot be fixed from an addon, and an earlier comment in
`RefuseUnlessAccessible` claiming the summary reached it was simply wrong. That
comment is corrected in the same change, because a wrong note about why
something works is worse than no note.

What can be fixed is where the refusal is read. Statamic's save pipeline, in
`ui-C4XItfuR.js`, does this with a 422:

```js
const { errors: messages, message } = e.response.data;
if (errors) errors.value = messages;
Statamic.$toast.error(message);
```

The errors go onto the publish container under the key the endpoint sent, which
is the listener's own `a11y_gate`. So the panel reads them from there and draws
every line in the same red alert a failed check gets, next to the button that
would have said the same thing had the author pressed it first.

Rejected: overriding Statamic's exception handler to change the toast text. It
would fix the words and not the corner, and an addon that replaces a
control-panel exception handler changes the wording of every unrelated validation
failure on the site.

Rejected: attaching the findings to a real field handle so Statamic renders them
inline. That would put accessibility errors under whichever field was chosen as a
host, which is a lie about where the problem is.

**Whichever answer is newest is the one on screen**, and it took a real author to
find that this has to run both ways. The first version only let a check supersede
a refusal, reasoning that a refusal left up after the page was fixed describes a
past that is no longer true. Correct, and half the rule.

What happened: a save was refused, Check was pressed to see why, and Save &
Publish was pressed again with nothing changed. The refusal was real and current,
and the panel hid it behind the older check result, so the second refusal drew
nothing at all and the only sign left was Statamic's toast in the corner. Straight
back to being told the data was invalid and nothing else.

A refusal now clears any older check result as it arrives. Statamic hands a new
errors object to the container on every failed save, so this fires on each
refusal even when the findings are word for word the ones already shown. Both
directions are pinned by their own assertions, because neither would fail loudly.

**What was not checked**: how it looks, and whether the container's errors arrive
exactly as read. There is no browser in this session. Both halves of the
mechanism were read in Statamic's own source, and the panel degrades to today's
behaviour if the key never appears: no alert, and the toast still fires.

---

## 2026-08-13: A scheduled post can be checked, and the gate stops blocking scheduling

Publishing was not the only thing standing between the gate and a page. A
collection can be set so entries dated in the future are private, which is how
scheduling works in Statamic, and `DataResponse::handlePrivateEntries()` throws
the same 404 for those as it does for a draft.

The consequence was much worse than a panel that would not answer. The gate does
not read "could not check" as a pass, on purpose, so with the gate refusing,
**every scheduled post on such a collection was unpublishable**. An accessibility
tool that stops a site scheduling posts is worse than no accessibility tool: it
gets switched off, and nothing gets checked at all.

The fix is the same shape as the published flag already there: for the length of
the render the entry is nudged to a date the collection is willing to show, and
the original is put back in `finally`. Nothing is saved. An entry left holding
today's date would be written with it by the next save that touched it, silently
unscheduling a post nobody unscheduled, so restoring it is not tidiness.

Rejected: a Live Preview token, which is Statamic's own answer to this. It was
rejected for the drafts case and the reasoning holds unchanged. A token means a
write to the token store and a stored copy of the entry, both of which exist so a
*browser* can request the front end. This renders in the same process.

Two details worth naming:

- **The URL is built after the date is settled, not before.** A collection routing
  on the date ("/{year}/{month}/{slug}") gives a different URL once the date
  moves, and requesting the old one would 404 on a page that was about to render
  perfectly well. This site does not route that way, which is exactly why it
  would have gone unnoticed.
- **A collection can hide every date there is.** Future private and past private
  together leave no visible date to nudge to. The loop tries now, then a year
  ahead, and then stops: the 404 is reported honestly as "could not check" rather
  than papered over with a date that changes nothing. Pinned by its own test.

Checked against the real site the gate runs on: its blog collection has
`future: private`, and a post dated a month ahead now checks clean and comes back
still dated a month ahead.

**Not a bug, and worth writing down because it looked like one.** A social share
image with no alt text reports nothing. That field feeds `og:image` and the
JSON-LD, and never becomes an `<img>` on the page, which was read in the site's
own template rather than assumed. The checker checks the rendered page. An image
that is not on the page has no alt text to be missing, and inventing a finding
for it would be the addon reporting on markup it never saw.

---

## 2026-08-13: The panel checks a page that does not exist yet

The panel told an author to save the page before checking it. On a collection
that publishes by default, there is no way to save without publishing, and the
gate refuses to publish a page with a problem. So the panel's advice was the one
thing the gate would not allow, and an author with a broken new page had nothing
to work with but a refusal toast that names a single finding.

Not an edge case. It is every page anybody writes, the first time they write it.

**The obstacle was that the panel does not know where it is.** Statamic's create
screen has no entry reference in its view data, only its edit screen does, and it
never calls `setParent()` on the blueprint either, so a fieldtype cannot ask
which collection it is sitting in. Read in `EntriesController::create()` against
`statamic/cms v6.27.2`.

Rejected:

- **Parsing the collection out of the page URL.** The panel already refuses to
  read the URL, for the reason that holds here too: it breaks the first time a
  route changes and no test catches it.
- **Reading the Inertia page props.** Same objection with more steps. It couples
  the panel to the shape of a screen that Statamic is free to change.
- **Leaving it, and telling people to set their collections to draft by
  default.** That makes every site change its publishing habits to suit an addon.

Taken: the listener that places the field stamps the collection and blueprint
handles into the field's own config, and `Field::toPublishArray()` merges raw
config, so both reach the browser as the component's `config` prop. The one piece
of code that already knows the answer writes it down where the panel can read it.
Both halves were verified in the compiled control panel rather than assumed:
fieldtype components are handed `config`, and undeclared config keys survive
`preProcessedConfig()`.

The endpoint then builds the entry the way Statamic's own create screen would:
collection, the blueprint's processed values, a date if the collection is dated,
and a slug from the title. Nothing is saved, and a test asserts the collection is
still empty afterwards, because an endpoint that checks a page must never become
a way to create one.

Two decisions inside that are worth naming:

- **Permission is `create` on the collection, not `view` on an entry.** There is
  no entry to check against, and this renders a page from values the caller
  supplies, so it must not be a way into a collection somebody cannot write to.
- **A page with no title is refused rather than checked.** Found by asserting the
  harmless outcome and getting a rendered one. An entry with no slug does not
  produce "no URL": it produces the collection's route with a hole in it, which
  renders whatever lives at that address and reports back about a page the author
  never opened. Reporting somebody else's findings as yours is worse than
  refusing, so it asks for a title.

**A hand-placed field still asks for a save.** `ensureField` leaves an existing
field's config alone, so somebody who turned the toggle off and placed the panel
themselves has no collection stamped on it. It says so instead of guessing which
collection was meant.

Checked against the real site rather than only the suite: an entry that has never
been saved renders through hada.farm's own templates at 16324 bytes, with the
unsaved title and the unsaved body both present in the output, and nothing
written to disk.

---

## 2026-08-13: Colour the message, not the space beside it

Third attempt at the same defect, and the first two are worth keeping because the
pattern is the point. A check that could not run drew nothing. Fixed. It drew a
grey sentence where a grey sentence already was, and was reported as the button
doing nothing while the request returned 422 with the right words in it. Fixed
with a badge. The badge is a small word in a small pill above the sentence, and
it was missed the same way.

Each fix was correct and each one was too quiet. What was actually wanted, and
was said plainly the third time, is for the message itself to change colour.

So `ui-alert` replaces the badge and the description in both failure paths:
`warning` for an entry nobody has saved yet, `error` for everything that really
went wrong. Statamic's own component, checked in `statamic/cms v6.27.2`: it takes
`text`, `heading`, `variant` and `icon`, and `variant` accepts exactly `default`,
`warning`, `error` and `success`.

Two things settled it over anything hand-rolled, beyond the standing rule against
inventing controls inside an accessibility product:

- **It carries `role` and `aria-live`.** Read out of the compiled component, not
  assumed. So the result is announced and not only shown. An accessibility addon
  whose own result reaches some readers and not others would be indefensible, and
  the request that prompted this said "for those who can see", which is the half
  a colour serves.
- **`ui-alert` is registered.** Also verified rather than assumed, by finding the
  registration in the control panel's own bundle: every export from Statamic's UI
  index becomes `ui-` plus its kebab-cased name. A tag Vue does not know renders
  as nothing, which is the exact failure this change exists to stop, so guessing
  was not an option.

A clean result keeps its quiet green badge. "Nothing to fix" does not need the
eye dragged to it, and an alert for good news trains somebody to ignore the ones
that matter.

**The lesson worth more than the fix**: every one of the three attempts passed
its tests. No test in this repository can say a thing is too quiet to notice, and
the one added below says so out loud rather than implying the surface is covered.

---

## 2026-08-13: Amber for "not yet", and the panel gets tested without a toolchain

The panel went quiet twice in one day. First it drew nothing at all when a check
failed, for the reasons in the entry below. That was fixed, and it was reported
again the same way: the button does nothing. It was doing everything. The request
went out, came back 422, and the correct sentence was on screen.

It was drawn as a bare `ui-description`, which is the same small grey line, in the
same place, as the idle hint that was already sitting there. Pressing the button
swapped one grey sentence for another. Nothing moved and nothing changed colour,
and no reasonable person reads that as an answer. A message nobody notices is not
meaningfully different from no message.

So a failure now wears a badge, the way a result does. Two of them:

- **Red, "Could not check"**, for anything that actually went wrong: the entry is
  gone, the caller cannot view it, the server erred, the request never landed.
  Same badge the gate already uses when it cannot render the page, because to an
  author those mean the same thing.
- **Amber, "Not checked yet"**, for the endpoint's 422, which today means one
  thing: nobody has saved this entry. Red was shipped for this case first and
  then changed, because red says something is wrong and nothing is. An addon
  whose entire discipline is not overstating what it knows cannot afford to shout
  at somebody for not having pressed save.

Amber is already the warnings colour. That is not a collision: warnings are a
result and this is the absence of one, and the two branches cannot draw together.

**The second decision here is how any of that gets tested.** Everything above is
in JavaScript, and this addon has no npm, no build step and no bundle on purpose.
The endpoint had tests throughout while the half an author actually looks at had
none, which is exactly why the same defect shipped twice.

Rejected: adding vitest, a package.json and a node toolchain to a repository
whose product is one PHP class and a list of rules. That is a large permanent
cost for one file.

Taken instead: the badge choice is pure logic, so a plain node script pulls the
expressions out of the panel source and runs them against fabricated errors, and
a Pest test shells out to it and skips when node is absent. It asserts nothing
about how anything looks, which is honest about what it can know. It asserts that
"not saved yet" and "something went wrong" cannot quietly end up wearing the same
badge. Mutation-tested twice: changing 422 to 419, and deleting the assignment
entirely. Both fail, and the failure output names the case rather than the exit
code.

What is still untested is everything visual, and that is a real gap rather than a
solved problem. The mitigation is that both badge branches reuse markup already
rendering correctly on a live control panel rather than introducing anything new.

---

## 2026-08-13: A failed check is guarded twice, because either guard alone can go quiet

Found on a real site. Pressing Check on an entry that had never been saved showed
a spinner and then the panel's idle text again: no result, no error, nothing. The
same screen as a page with nothing wrong with it, which is the one outcome this
product is not allowed to produce.

The cause was two correct-looking lines meeting.

Statamic's create screen has no `reference` in its view data at all, only its edit
screen does (`EntriesController::create()` versus `::edit()`, read in
`statamic/cms v6.27.2`). So the panel had nothing to send, and the endpoint's
`abort_if(..., 404)` fired. Laravel answers a bare `abort()` with
`{"message": ""}`. The panel then did
`e.response?.data?.message ?? 'The check could not be run.'`, and `??` steps in
only for null, so the failure message became the empty string. The template tests
that value for truth before drawing it, so the failure branch never rendered.

Neither line is wrong on its own. An empty message is a reasonable thing for a
404 to carry, and `??` is the usual way to default a missing key. The defect only
exists where they meet, which is why fixing one of them was rejected:

- **Fix the panel only** (`||` instead of `??`). Correct, one character, and it
  leaves the endpoint free to keep answering failures with nothing. The next
  `abort()` written here is silent again, and no PHP test would catch it.
- **Fix the endpoint only** (a message on every failure). Also correct, also
  leaves the panel one empty string away from drawing nothing, including for
  failures that never reach this controller at all.

Both were done. The endpoint now gives every refusal a sentence, and the suite
asserts that invariant across all three failure shapes rather than one at a time,
because no single-case test would have caught this one returning. The panel now
falls back on any falsey message, not just a missing one.

The message an author gets for the case that started this says what to do: this
page has not been saved yet, so save it once and then check it. Making the panel
work before the first save is a real improvement and is not this change. It needs
the collection handle, and the publish container does not carry one: it has
`reference` and `site` and nothing else that identifies a collection. Reading it
off the page URL was rejected for the reason the panel already avoids the URL: it
breaks the first time a route changes, with no test to catch it. The remaining
route is a `preload()` on the fieldtype, which is worth doing on its own terms and
not while fixing a silent failure.

**What was not checked.** The panel change was verified by reading and by
confirming the operator semantics directly, not by clicking the button in a
browser. There is no JavaScript test harness in this repo, so the invariant that
is actually enforced by tests is the endpoint's: every failure carries a non-empty
message. That makes the panel's own fallback belt and braces rather than the only
thing standing between an author and a silent pass.

---

## 2026-08-12: Settings belong in the control panel, because of who ends up holding the site

Everything configurable was a PHP file and a terminal command. That is fine for
the person who installs the addon and useless for the person who ends up with it.

**The chain that was being ignored.** A developer installs from the marketplace
and hands over. The person left holding the site opens the control panel, wants
the gate to report rather than refuse while a backlog is cleared, and cannot edit
`config/statamic-a11y-gate.php` or run `php artisan config:clear`. They email the
developer, who has moved on. For a free addon with no support contract, that is
where a setting goes to die.

Statamic gives the screen away: a `resources/blueprints/settings.yaml` is the
entire implementation, and the route, controller and storage already exist. Four
things moved onto it: what happens when a page has a problem, which collections
to check, whether to add the panel automatically, and the two opt-in checks.

**Every word on it is written for that person.** A test fails if the screen says
"blueprint", "handle", "yaml", ".env" or a file path, which is the same rule the
panel already answers to: the reader can act, or the words are noise.

**The screen wins once saved; the file answers until then.** A screen that
silently lost to a file would be worse than no screen, because somebody changes a
setting, saves, sees nothing happen, and stops trusting the rest of the addon.

**Three traps, all found by running it rather than reading it.**

An unsaved settings record is not empty: it comes back carrying the blueprint's
own defaults. Merging its values would let a field default beat a config file
somebody wrote on purpose, so what matters is whether a record *exists*, not
whether it has values.

`all()` blends those defaults over what was saved even on a record that does
exist. Somebody who opens the screen, flips one toggle and saves would have
turned a site configured to report into a site that refuses, because "refuse" is
that field's default. `raw()` is exactly what was saved and nothing else. This
one survived four mutations and was only caught by writing the specific case.

And the slug. `FileSettings::path()` writes to `resources/addons/{slug}.yaml`
while `FileSettingsRepository::find()` reads
`resources/addons/{everything after the slash in the package name}.yaml`. With a
slug of `a11y-gate` on a package called `bpmore/statamic-a11y-gate`, saving
worked and loading never did. **An addon whose slug differs from its package name
cannot use Statamic's own settings storage**, so the slug is now
`statamic-a11y-gate`, which renames the config file with it. Nothing had shipped,
so the cost was a rename and one `php artisan statamic:addons:discover` on the
test site.

**Mutation-tested, five run, five killed**, and the fourth is the one worth
keeping: reading `all()` instead of `raw()`, the screen never winning, falsey
answers thrown away, the settings blueprint deleted, and an untouched field on
the screen overruling the file.

**Rejected.** *A screen for placing the panel in blueprints*, because Statamic
has one and it is the blueprint editor. *Config file wins*, which protects a
developer's deployment and breaks the screen for everyone else.

---

## 2026-08-12: The addon can put its own panel in blueprints, off unless asked

Adding the panel to a site meant editing every entry blueprint by hand. On a
small site that is seven files. The addon can do it itself, and now does, behind
`add_panel_to_blueprints`.

**The mechanism is Statamic's own.** `slug` and `date` are not in anybody's yaml
either: they are added to the blueprint as it is found, through
`EntryBlueprintFound` and `ensureField`. Using the same call means the field
behaves like a native one and disappears cleanly when the addon is removed,
rather than leaving an orphaned handle in a file somebody has to go and delete.

**Off by default, and that is the decision rather than the feature.** An addon
that rearranges publish forms on install is one that somebody who did not choose
it will uninstall by lunchtime. The first run of this addon should surprise
nobody.

**Three things it refuses to do**, each because the alternative is a field
sitting in a form saying nothing useful:

- A collection with no route never gets it. No pages means nothing to check.
- A collection the `collections` setting excludes never gets it. If the gate is
  not watching it, a panel offering to check it is an odd thing to draw.
- A blueprint that already carries the field keeps its own placement and does
  not get a second copy, because `ensureField` matches on the handle.

**The collection is read off the blueprint's namespace, not off the event's
entry, and this is the bug that was nearly shipped.** `EntryBlueprintFound`
carries an entry only sometimes: Statamic dispatches it from the entry when
there is one, and from the collection when there is not. The second path is what
happens when somebody presses Create, which is the first time an author ever
sees the form. Reading the collection from `$event->entry` would have passed
every test written before that was noticed, and left the panel missing at
exactly that moment.

**Mutation-tested, four run, four killed:** the flag ignored, routeless
collections included, the collection filter ignored, and the default flipped to
on. The last one survived first time and is worth naming: every test set the key
explicitly, so nothing exercised the fallback that decides for a site whose
published config predates the setting. That is precisely the site the surprise
would land on.

**Rejected.** *A settings screen for choosing collections*, because Statamic
already has one for this and it is called the blueprint editor: it knows which
tab, what order and what display name, and a second screen would answer none of
those. *On by default*, which is the better first run and the worse first
impression.

---

## 2026-08-12: The whole-site scan, and why the grouping is the feature

`php please a11y:check` renders every published page and groups the findings by
how many pages each one is on.

**The grouping is the only thing here that the panel could not already do.** A
problem on one page is something somebody wrote. The same problem on every page
came from a template. The first costs an author an afternoon; the second is one
fix worth every page at once, so it sorts to the top. Everything else in this
command is a loop and a printer.

**The grouping key is the rule plus the pointer, and both halves earn their
place.** On the rule alone, every undescribed image on a site collapses into one
line reading "12 pages", which is true and useless. With the page included,
nothing groups at all. The pointer is the text or the file that tripped the rule,
so one card grid on forty pages becomes one line and forty different images stay
forty lines. Both are correct.

**It says "every page" only when that is literally true of the pages scanned.**
That phrase is what turns a list of findings into a decision about the theme, and
it has to be earned. Everything else is a count. The step from "on all 38 pages"
to "so it is in your layout" is left to the reader, because a checker reading
HTML cannot see a template and should not claim to.

**A command, not a screen.** The reader is a developer, it can exit non-zero so a
release can be stopped by it, and rendering several hundred pages inside a web
request is how you meet a timeout. On hada.farm it read 38 pages and found the
same four problems the panel finds one page at a time, which is the check that
matters: the scan calls `PublishGate::examine()` rather than reimplementing
anything, because two ways of checking a page is two sets of answers.

**A page that could not be rendered fails the run.** Same fail-closed rule as the
gate. A build that went green because four pages failed to render would be worse
than no check at all, so those pages are named and counted separately, never
folded into the clean total.

**Mutation-tested, five run, five killed:** grouping on the rule alone,
unreadable pages no longer failing the run, warnings failing the run, the sort
order reversed, and unreadable pages skipped silently.

**Rejected.** *A control-panel screen*, which is the same work behind a request
timeout and aimed at somebody who cannot act on it anyway. *Labelling findings
"template" or "content"*, which is an inference this cannot support: it never
sees a template, only a page count. *A second addon for it*, closed by the market
entry above: at these install volumes, two listings split a handful of users into
two smaller handfuls and re-create the forked-rules problem.

---

## 2026-08-12: Free, and still proprietary, because the licence never protected the revenue

**Decision.** The addon ships free. No price, no licence key, no per-site limit.
The licence stays proprietary: the no-reuse clause and the conformance disclaimer
survive, everything about purchasing is struck.

This reverses the pricing half of "Source-available and proprietary, modelled on
Statamic's own" below, and leaves the licensing half exactly as it was. Recorded
as its own entry rather than edited into that one, because the reasoning that
survived is more interesting than the part that did not.

**Why free.** There is not enough money in a Statamic accessibility addon to be
worth the overhead of selling one. That is a market judgement, not a change of
heart about the work.

**Why NOT MIT, which is the obvious move for a free addon and is wrong here.**

The mistake is assuming the licence protects addon revenue. There is no addon
revenue to protect. What the licence protects is the engine: this codebase is a
deliberate fork of another product's accessibility checker, same rules, same structure,
same corpus. MIT-licensing the addon MIT-licenses that engine, and hands it to
anyone who wants to build against the product it came from.

That is not a hypothetical. **A11yamic** listed on the Statamic Marketplace on
2026-07-08 at $35 per site, and already ships live editor checks, an axe-core
browser audit, template-versus-content splitting, and per-rule blocking. A
permissive licence would give a direct competitor the rule set for nothing.

**The adoption argument does not apply here either.** Restrictive licences tax
adoption for libraries a developer embeds in their own product, where the licence
has to be read, understood and accepted by whoever ships it onward. This is a CMS
addon installed from a marketplace. Almost nobody reads the licence, and nobody
is forking it to redistribute.

**Rejected.** *MIT*, for the reason above, and it was the recommendation until
the engine argument was pointed out. *Apache 2.0*, same problem plus a patent
grant nobody here needs. *Keeping the paid clauses and simply never charging*,
which would leave a licence describing a transaction that does not exist and make
every other sentence in it less believable.

**What changed in the file.** Struck: one-licence-one-site, the try-before-you-buy
line, and the do-not-circumvent-licensing clause, which guarded a validation
mechanism that will now never exist. Kept: no resale or reuse in another product,
keep the notice, follow the law, and the whole conformance disclaimer.

The no-reuse clause gained the reason it exists, written into the licence itself
rather than left in this log, because that is the one clause a reader is most
likely to think is a mistake in a free product.

**Still true, and worth saying plainly:** free to use is the intent, free to take
is not.

---

---

## 2026-08-12: What the market actually looks like, counted rather than assumed

The commercial reasoning in this file was written before anybody looked. Here is
what is there, with numbers, and it changes what this project is for.

**The platform.** `statamic/cms` has 3,834,308 installs all time and 146,814 a
month. 4,867 GitHub stars. A real ecosystem, and a small one.

**The marketplace.** 475 addons listed. In the first 60, **17 carry a price**,
which is 28%. Observed prices: $10, $15, $25, $35, $40, $49, $59, $75, $79, $99,
and one at $199. A free tier alongside a paid one is the house style, not an
exception: Advanced SEO is $0 to $75, Mapbox $0 to $15, Lead Insights $0 to $40.
That settles the editions question in favour of one package with two editions
rather than two packages.

**How many people pay, which is the number nobody publishes.** Packagist install
counts for recently listed paid addons:

| Addon | Price | Listed | Installs |
|---|---|---|---|
| Linkwise | $49 | 26 May 2026 | 8 |
| Postmaster | $99 | 24 July 2026 | 26 |
| Advanced SEO | $0 to $75 | established | 19,166 all time, 763 a month, mostly the free tier |

Installs are a proxy rather than a sales figure, and it is the best one
available: selling on the marketplace requires publication to Packagist, so a
paid addon that sells shows up here. **A new paid Statamic addon gets installs in
the tens.**

### The competitor, which did not exist when this project started

**A11yamic**, by 30Bit, **$35 per site**, first published 8 July 2026, at v1.0.1,
Statamic 6 only. It ships:

- live checks in Bard and Markdown as the author types
- an alt-text manager with coverage percentages
- **a page audit running axe-core in the browser**
- **findings split into template and content**
- an accessibility statement generator citing WCAG 2.1 AA, EAA and EN 301 549
- per-rule off, warn or **block**, enforced server side
- a dashboard score widget

Two of those are things this project had filed as its own future work: the
browser pass was the argument for a *second* product, and the template-versus-
content split was the shape the starter-kit scan was going to take. Both are
already in somebody's $35 addon.

**Its Packagist installs: 1 total, 0 in the last month**, five weeks after
listing. So the space is unproven rather than crowded, which cuts both ways: no
competitor has demonstrated demand either.

### What this changes

**There is no revenue case here, and pretending otherwise would infect the
product.** At $35 and observed volumes, a strong first year is a few hundred
dollars. Any decision justified by "it will sell better" is being justified by a
number nobody has. This is a dogfood tool for two real sites, a portfolio piece
with an unusually defensible engineering story, and a wedge into the work that
does pay, which is services.

**The one addon question is closed.** At these volumes, splitting the work across
two listings splits a handful of installs into two smaller handfuls, and
re-creates the forked-rules problem this project was founded to manage.

**The differentiators that survive are the refusals, and they are narrow.**
A11yamic ships a site score and a generated conformance statement. `CLAUDE.md`
forbids both: a score is a claim automated checking cannot support, and nothing
this produces is a statement of conformance. That is a real difference and it
sells to exactly one buyer, the one who has been burned by an overlay or has to
defend a claim to somebody hostile. It is not a mass-market pitch and should
never be dressed as one.

**Where the money would be, if it is anywhere.** Craft has a larger paid plugin
market and nearly all of the work here transfers. The build-time CLI reaches
every static site rather than one CMS. Both were already in the upstream plan's
"where else this goes" section, and this is the first evidence that they matter
more than the Statamic listing does.

**Rejected.** *Racing A11yamic on features*, which means adding a score and a
statement generator, which means breaking the two rules this project is built on
to compete for a market worth a few hundred dollars. *Abandoning the addon*,
because two sites use it, the engine is the shared asset, and the listing costs
nothing to keep.

**What was not checked.** Whether A11yamic's single install is real: a paid addon
could reach buyers some other way, though the marketplace requires Packagist.
Nothing about Craft's plugin economics was measured, only asserted from
reputation. And no potential buyer has been asked anything at all, which is the
cheapest research left and the only kind that would settle it.

---

## 2026-08-12: Say what the reader can act on, and say the rest where it belongs

The rule in `CLAUDE.md` that everything else rests on: a check that could not run
must say so, because a silent zero is indistinguishable from a pass. Until this
change the addon broke that rule on every page it checked.

**Three extents, not two, and each one exists to stop a specific lie.**

- `full`: the check saw everything it judges. **A page with no images is a full
  pass for the image rule**, not a gap. That is why there is no fourth extent
  called "nothing to judge": it sounds more precise, and it would make a clean
  page look like a hole.
- `partial`: it ran, and part of what it judges was invisible here.
- `none`: it could not run at all, because what it reads is not in this page.

**Coverage is about the page in front of the author, not about Statamic in
general, and the first version got that wrong.** It reported all seven families
on every page, so a plain blog post with no video and no figures was told that
captions, transcripts, figure text and footnotes had not been checked. Reading
the live panel, the owner asked the obvious question: does an author care, if
there are no videos on the page?

They do not, and the rule I had already written said so. A page with no images is
a full pass for the image check because there was nothing there to miss. The same
sentence applies to a page with no video, and applying it to one and not the
other was an inconsistency, not a policy.

So coverage now turns on what the document contains:

- **Video, audio and figures** is full on a page with no media, and partial on a
  page with any, where the hole is real and specific: the embed title was
  checked and its captions cannot be.
- **Touch target size** is full on a page with no controls, and partial on a page
  with any.

**And two checks left the per-page report entirely.** Links-to-unpublished and
reading level read markup that leaves no trace in a finished page, so on a site
that has not integrated them there is no page where they could ever say anything
useful. They are a setting, `opt_in_checks`, named in the config file with what
each one reads, which is the one place a standing "this was not checked" notice
gets read at all.

**The checks still run.** Only the coverage line is opt-in. A site that starts
stamping before it edits a config file gets its findings, and gets told the check
ran, rather than silence. That distinction is the whole reason this was a
reporting change and not a switch that turns checks off.

On hada.farm, live, a real blog post now reads: *4 of 5 checks ran in full, 1 ran
partly.* Before the correction the same page read *3 of 7 in full, 2 partly, 2
could not run here*, and four of those five lines were about things the page did
not contain.

**The line a warning has to earn.** A notice that appears on every page whatever
it contains is the one people learn to scroll past, and they take the real
warning with them when they go. That is a worse failure for this product than
saying slightly less, because the whole thing is sold on the reader believing
what it says.

**And then the whole thing was pointed at the wrong reader, which took a third
pass to see.** With the noise gone, the panel still said *"4 of 5 checks ran in
full, 1 ran partly"* and, behind a disclosure, *"a size set in a stylesheet needs
a real browser"*. The owner asked how that helps somebody writing a page. It does
not. It is an auditor's number and a theme author's problem, put in front of a
person who can change neither from an entry screen.

**So the audiences were split, and the test for which side a line falls on is
whether the reader could do anything about it.**

- **The author sees** what to fix, and gaps in *their own content* that only they
  can settle. Today that is one sentence, on a page carrying media: "Captions
  were not checked. Only you can confirm this video or recording has them."
- **The standing limits moved off the result entirely**, after the same question
  was asked of them: does a sentence about theme stylesheets, printed under every
  scan, mean anything to somebody writing a page? It does not. They are a page
  under Tools now, linked from the panel as "What this can and cannot check",
  and the panel shows the link rather than the lecture. That page is also where
  the sentence a buyer's lawyer will read now lives, in exactly one place: a page
  it finds nothing wrong with has not been proven accessible.
- **The auditor sees the counts**, in the data the check endpoint returns and in
  this log. Not drawn in the panel, not in the refusal.

`CLAUDE.md` says a check that could not run must say so. It does not say it must
be said in an auditor's words to a person writing a blog post, on every entry,
forever. Said once, plainly, where it will be read, is the same honesty and it
survives contact with a real reader.

**The corpus does not pin any of this, and that is a stated gap rather than an
oversight.** Coverage is new behaviour the other implementation does not have, so a
corpus demanding it would be unanswerable on the other side, and the corpus's own
rule is that it holds both projects to the same statements. `check()` still
returns findings alone and the corpus still runs against it, untouched. When
it implements coverage, the expectations go into the corpus in one commit in
both repositories. Until then the two projects agree about findings and are
silent about coverage, which is the arrangement working rather than failing.

**Mutation-tested, ten run, ten killed**, though one survived first time and is
worth naming. The kills: a host-markup check claiming full coverage, target size
claiming full, the coverage line dropped from the refusal, the endpoint sending
an empty summary, a family quietly missing from the list, media reporting full
with a video on the page, target size reporting full with controls on the page,
an opt-in check reporting when the site never opted in, and an opt-in check
staying silent when it did.

**The survivor:** emptying the opt-in list where the settings are read broke no
test at all, because every test that cared passed the list directly to the
checker. The setting was a config key nothing proved was read. Closed with a test
on `GateSettings`, which is exactly the seam a mutation is for finding.

**Rejected.** *Per-rule coverage* rather than per-family, which is more precise
and produces seventeen lines an author has to read to learn one thing. *A single
count* with no reasons, which tells somebody they have a problem and nothing
about what it is. *Keeping the opt-in checks in the per-page report*, which is
the version that was built first and which put four lines about absent things in
front of an author on every entry. *Keeping the count behind a disclosure*, which
was the second version: better than a wall of text, and still an auditor's
sentence in an author's panel.

**A fourth pass, on how it looked rather than what it said.** The guide page was
plain HTML with borrowed Tailwind classes, and beside Statamic's own utilities it
read as a document dropped on the page: no header, no cards, and a blank space in
the Tools list where every other entry had an icon.

Both were the same mistake as reaching for a hand-rolled widget, and both were
fixed by reading the build rather than guessing. **A utility view is compiled as
a Vue template**, not printed as HTML: `DynamicHtmlRenderer` does
`defineComponent({ template: html })`, so `ui-header` and `ui-card-panel` resolve
inside a Blade file exactly as they do in the control panel's own pages. And a
named icon is looked up in Statamic's set, where `shield` does not exist, which
is why nothing was drawn. `clipboard-check` does.

Tests hold both now, because both failed silently: an icon that is not there
renders as nothing, and a page with the wrong chrome renders perfectly well.

**Worth naming, because it is the pattern rather than the incident.** This
feature was wrong four times and each time the code was correct and the tests
passed. Noisy on absent content, then noisy behind a toggle, then addressed to
the wrong person, then dressed in its own styles beside a control panel that
ships its own. Every correction came from somebody looking at the screen and
asking what a reader would do with what they saw, which is a question no test in
this repository can ask.

---

## 2026-08-12: Run against a real site, which found a bug the whole suite had passed over

The gate was switched to refuse mode on hada.farm (Statamic 6.27.1) and pointed
at a deliberately broken scratch entry. **It refused the publish, and the entry
was still a draft on disk afterwards.** The message an author would be handed:

> This entry was not saved. 3 accessibility problems have to be fixed first.

followed by the three problems in plain language with the fix for each. That
closes the question the earlier entry left open about whether a refusal carries
its reason on a real site rather than in a test.

**Then every published page on the site was checked: 38 pages, 4 findings, 3
pages affected.** Worth writing down because the number that matters commercially
is not how much a gate finds, it is how much it finds wrongly. A gate that lit up
thirty pages on the day it was installed would be switched off that afternoon.

The four are real:

- Three links whose text is a bare domain (`eiaeo.app`, `seedfile.app`,
  `pacewell.app`). That is what WCAG 2.4.4 is about, and the check is right.
- One heading jumping to h3.

**And one false positive, found on the scratch page.** A link whose text is the
title of another post on the site, *"Link Text That Leads Somewhere: No More
Click Here Dead Ends"*, was reported as `link-unclear`. The text is about as
descriptive as link text gets. It failed because `banned_substrings` contains
"click here" and the rule flags any name that *contains* it, on the reasoning
that "click here to read the report" is still "click here".

**Not fixed here, on purpose.** That rule is ported from upstream and both
projects answer to the shared corpus, so changing when it fires is a corpus
change, in its own commit, in both repositories, with the reason written down.
Fixing it quietly on one side is the exact divergence the corpus exists to
prevent. It is recorded as a known false positive until that happens.

Worth noting how narrow it is: the post whose title that is checks clean. The
finding only appears on pages that *link* to it, which is what makes this kind of
false positive hard to spot in a fixture and easy to spot on a real site.

**And a real bug, which every test in this project had passed straight over.**
The panel on a draft answered "could not check: the page threw while rendering".
`DataResponse::handleDraft()` throws a 404 for an unpublished entry unless the
request carries a Live Preview token, so the panel was useless in the one place
it is worth the most: before the page goes live. Every fixture entry in the suite
was published, so nothing ever reached that branch.

Statamic's answer is a token. This is not one, deliberately: a token means a
write to the token store and a stored copy of the entry, and both exist so a
*browser* can request the front end. The addon renders in the same process, so
the published flag on the in-memory instance is flipped for the length of the
render and put back in a `finally`. Nothing is saved, nothing is cached, and
there is no token to clean up. A test now covers the draft path and asserts the
flag comes back, on the instance and on disk, and both halves are
mutation-checked.

**The screenshot also showed a second defect in one line of copy.** The failure
read "the page threw while rendering: ." because Statamic's
`NotFoundHttpException` carries no message and only `getMessage()` was reported.
It names the exception class when the message is empty. **That fix has no test**,
and the comment beside it says so: the harness renders an undefined tag to an
empty string rather than throwing, so nothing in it can produce a message-less
throw.

Both of these were found by a person looking at a screen, after the suite was
green and after the same entry had been checked successfully from the command
line. Worth recording as the argument for doing this at all.

---

## 2026-08-12: The panel, built once the control panel stopped being guesswork

The panel is built: a fieldtype, one plain script, and the endpoint behind it.
`POST cp/a11y-gate/check` takes the publish container's own reference
(`entry::<id>`) and the form's current values, applies them the way Statamic's
own `PreviewController` does, renders, checks, and answers with the findings
split into the ones that refuse a publish and the ones that do not.

**Checking unsaved values is the whole point and it is proven.** A panel that
answered from the file on disk would be at its most wrong exactly when it
matters: after an author has broken the page and before they press publish. The
test for it fails if the supplements are not applied, checked by mutation.

**The three unknowns that stopped the component are answered, and none of them
needed a browser.** `vendor/statamic/cms/resources/dist-dev` ships the control
panel's build unminified, with `//#region resources/js/...` markers naming every
source file. Everything below was read there.

1. **Registering a component.** `Statamic.$components.register(name, component)`
   calls `app.component(name, component)`, and queues the registration when the
   app has not booted yet. So `accessibility_panel-fieldtype` resolves the same
   way `text-fieldtype` does.
2. **Reading the form's current values.** The publish container provides a
   context, and `window.__STATAMIC__.ui.injectPublishContext()` returns it.
   Among what it carries: `values` (a ref to the form as it stands),
   `visibleValues`, `meta`, `site`, `blueprint` and `reference`.
3. **Knowing which entry.** That same `reference`, which is `entry::<id>`, and
   which the endpoint now takes directly rather than making the browser parse an
   id out of it.

**And a fourth thing, which removed a whole toolchain.** Statamic aliases Vue to
`vue/dist/vue.esm-bundler.js` and puts it on `window.Vue`, so the runtime
template compiler is present and a plain script with a `template` string
compiles. A `package.json` and a `vite.config.js` were written and then deleted:
this addon needs no npm and no build step. The panel is one file, loaded as an
ordinary deferred script.

The panel is built from `ui-panel`, `ui-badge`, `ui-heading`, `ui-description`,
`ui-button` and `ui-skeleton`, all globally resolvable, with prop names read out
of each component's source rather than guessed. Nothing is hand-rolled, which is
the rule for this product in particular.

**Run against a real site, which is the part no unit test could do.** The addon
was installed into hada.farm (Statamic 6.27.1, real templates, real content) from
a path repository, in warn mode so nothing could be refused.

- A real blog entry rendered through the site's own templates in **265ms** and
  came back as 28,148 bytes with one h1 and one image. Fast enough to sit in
  front of a save, and proof the render survives a real template stack rather
  than a fixture.
- Supplementing that entry with a second h1 and an image with no description
  produced exactly those two errors. That is the panel's whole mechanism, end to
  end, on a live site.
- **The mutation that the test suite could not kill was run there too, and it
  survived that as well.** Removing `substitute()` changed nothing: the
  repository hands back the same in-memory instance either way. The line stays as
  insurance against a repository that would not, and the renderer now says
  plainly that it is unproven instead of implying it was measured. The earlier
  entry claiming the spike proved that specific line has been narrowed to what
  the spike actually showed, which was about `setSupplement` rather than about
  `substitute`.

**Rejected on the way:** a shipped Statamic 6 addon in the same vendor directory
builds its control-panel UI as a Blade widget with inline styles. Not copied: it
is hand-rolling, and the rule against it exists for this product in particular.

**The limit that made the endpoint's tests worth writing carefully.** A blueprint
with no fields processes submitted values to nothing, silently. The first version
of the test passed while checking an unchanged page. It now loads a real
blueprint fixture, and the comment says why.

**It renders, confirmed by a person looking at it.** The field appears in the
entry sidebar on hada.farm. That settles the half that could not be settled from
source: the component registers, resolves under the name the fieldtype's handle
implies, and mounts with the injected publish context available, because a
failure in any of those would have thrown before anything drew.

**What is still unverified is the round trip.** Nobody has pressed Check this
page and watched findings come back. The endpoint is tested and the mechanism was
proven on this site through PHP, so what remains untested is the browser half of
the call: the axios instance, the CSRF header, and `cp_url`. That is one click,
and until somebody does it this stays written down rather than assumed.

**Rejected.** *A dashboard widget listing every entry with problems*, which needs
to render every page in the site to answer and is not the question an author has
in front of them. *A panel that checks the saved version only*, which is buildable
without any of the three unknowns above and was turned down because it is stale
in exactly the case it exists for.

---

## 2026-08-12: The gate refuses by throwing, and publishing means the state the save leaves behind

The addon exists: a service provider, a config file and one listener. Installing
it into a Statamic site now refuses a save that would leave a published entry
with an accessibility error.

**The refusal throws a `ValidationException` rather than returning `false`. That
was the open implementation question and it is settled.** Returning false does
stop the save, verified by running it. What it cannot do is say why: the control
panel's save endpoint answers `'saved' => false` with nothing attached, and the
publish action turns that into a red "Couldn't publish entry" toast. An author
would be told the editor is broken rather than told their page has a problem,
which is the failure this product exists to prevent.

**A correction to an earlier entry, which was wrong in a way that mattered.** It
recorded the compiled control-panel JavaScript as absent from the vendor package,
and left what an author sees on a refused save as unverified. It ships, at
`resources/dist/build/assets`. Read out of it: the entry publish form's
`handleAxiosError` takes `message` and `errors` off a 422 and raises the message
as a red toast. That is what makes the exception the better answer, and it was
knowable all along.

**What the author sees today is one line, and the addon says so rather than
implying more.** Laravel builds the 422 `message` from the first validation
error, so the summary reaches the toast; the remaining findings arrive under a
key that matches no field in the blueprint and nothing renders them. The first
line is written to be useful alone ("This entry was not saved. 3 accessibility
problems have to be fixed first."). The panel that lists all of them is the next
piece of work. **Not verified in a browser:** the reading is from the shipped
JavaScript, not from watching a refusal happen in a control panel.

The cost of throwing, named because it is real: a refusal is now an exception
everywhere, including a script or an import that saves entries outside the
control panel. Accepted. A silent `false` in a deploy script is how a broken page
reaches production with nobody told.

**What counts as publishing: the state the save would leave behind.** There is no
publishing event, so an entry that will be live when this save finishes is
checked and a draft is not. Verified in source rather than assumed: Statamic's
publish path (`Publishable::publish()`, and `publishWorkingCopy()` when revisions
are on) sets published and calls `save()`, so both dispatch `EntrySaving` and
both arrive at the gate. A useful consequence with revisions enabled: working
copies save freely and only the publish is gated.

Re-saving an already live page is checked too. A page that a later edit breaks is
exactly as broken as one published broken.

**A page that cannot be rendered refuses the save.** `GateResult` has three
outcomes, not two, because "nothing was wrong" and "nothing could be checked" are
different answers and a gate that returned an empty list for both would be
indistinguishable from one that passed a page it never rendered.

**Two things found by running it that reading would not have caught.**

`substitute()` fatals on a brand new entry: it indexes by id and an entry being
created has none until `EntryRepository::save()` assigns one. The renderer now
assigns it first, the same way and with the same generator Statamic uses moments
later. If the gate then refuses, the entry carries an id and is never written,
and nothing reads it.

The gate cannot use the control panel's own request. That request is a PATCH to a
control-panel URL, so templates asking for the request would render the page as
though the visitor were in the control panel. A front-end request is bound for
the duration of the render and put back afterwards.

**Mutation-tested, seven run, six killed:** the listener returning instead of
throwing, "could not check" no longer refusing, warnings refusing like errors,
drafts being gated, warn mode refusing anyway, and the collection filter ignored.

**One survived, and the honest answer is that this suite cannot kill it.**
Deleting `substitute()` from the renderer leaves every test green, because in a
booted test the repository hands back the same instance either way and the line
has nothing to correct. On a real site it does: the render spike watched the
saved value render without it. So the evidence for that line is the spike, the
test that looked like it covered it now says plainly that it does not, and the
gap is closed by a browser or by nothing.

**Rejected.** *Returning `false` and adding a banner elsewhere*, which needs a
place to put the banner that does not exist yet, and leaves the refusal itself
mute in the meantime. *Gating on the entry becoming published rather than being
published*, which sounds narrower and would let a live page be broken by any edit
after the first.

---

## 2026-08-12: The checker is here, the corpus is enforced, and one hole in it is now known

The first executable code in this repository. It is the checker and nothing else:
no service provider, no listener, nothing installable into a Statamic site yet.
The README says so.

**The corpus runs, and it is the reason this was the first thing built.** All 19
cases, all 17 rules, green. The loader and every assertion are close to a copy of
the upstream `ConformanceCorpusTest`, deliberately: the point of a shared corpus is
that both suites hold their project to the same statements, and a tidier rewrite
on one side is how the two quietly stop asking the same question.

**The rules were ported verbatim, and the deletions are the interesting part.**
The upstream `Violation` carries `blockUid`, `fieldKey` and `breakpoint` so its
editor can highlight the block and focus the control behind an issue. A Statamic
entry rendered through the site's own templates has none of those, so all three
could only ever be empty strings here. They are dropped, along with `FieldTarget`
and the `blockMeta()` walk that fed them. The corpus pins none of it, so this
costs no agreement: an always-empty field is just an invitation to write code
that pretends to read it.

Two `config()` reads became constructor arguments, because the checker must stay
framework-free. The link-text word lists are now `LinkTextVocabulary`, whose
defaults are the upstream config values byte for byte. That class is where
the two projects would drift first and most quietly, because `link-unclear` and
`link-vague` are the only rules whose verdict depends on a list rather than on
the markup. `AccessibilityStandard` lost its registry, its `extends` chain and
its axe tag lists, keeping the one field a check actually reads: target size, 24
at AA and 44 at AAA.

The stamped attribute names were **not** renamed, though nothing in
Statamic stamps them. The corpus fixtures use those names, and renaming them here
would fork the corpus to no purpose.

**Mutation-tested, eight run, eight killed, but not on the first attempt.**
Killed: the pack reordered, a family deleted from the pack, a blocking rule
downgraded to a warning, a WCAG number cited for a heading rule that cannot
establish one, the corpus directory hidden, the skipped-heading comparison moved
off by one.

**One survived, and it found a hole in the shared corpus rather than in this
port.** Deleting the entire `alt=""` clause from `ImageAltCheck`, so that only a
missing attribute is reported, left the corpus suite green. Every image fixture
in `corpus/` either omits the attribute or fills it in, so nothing reaches the
branch. `alt=""` is the distinction that matters most in practice: it is what an
editor saves before a description is written, and it is also how a genuinely
decorative image opts out. Both forks are unguarded for it today.

Closed here with `tests/Unit/ImageAltCheckTest.php`, which kills that mutation
and a second one that ignores the decorative opt-out. **That is the local fix,
not the real one.** The real one is a corpus case added to both repositories in
one change, and it has not been done. Written down rather than quietly patched,
because a gap covered on one side only is exactly the asymmetry the corpus
exists to prevent.

**`composer.json` declares `"type": "library"`, not `"type": "statamic-addon"`.**
There is no service provider for Statamic to discover, and a package that
announces itself as an addon while containing none would fail at the first
install. It flips in the commit that adds the provider.

**Rejected:** *starting with the `EntrySaving` listener*, which is the visible
half of the product and the half that cannot be trusted without this one.
Rendering an entry and refusing a save are both verified spikes; a checker whose
answers nobody has pinned is not.

---

## 2026-08-12: Open questions, listed rather than assumed

Not decisions. Things that must be answered before they are decided by accident.

1. ~~**Licence.**~~ **Settled: source-available and proprietary, modelled on
   Statamic's own.** See `LICENSE.md`. The question got easier twice: once when
   the repository went public, so the licence stopped protecting the source, and
   again when the fork removed any need for it to accommodate a shared package.
   Outstanding: a lawyer has not read it.
2. ~~**Where the checker lives.**~~ **Settled: this repo carries its own copy.**
   The option the upstream plan forbade, chosen deliberately by the owner, with
   the drift risk answered by a shared conformance corpus rather than ignored.
   Reasoning in the entry below.
3. ~~**Marketplace requirements.**~~ **Read, and the answer changed question 1.**
   Selling requires a package on packagist.org, so the repository must be public.
   Licence enforcement is a banner, not a block. Details in the next entry.
4. ~~**The spike.** Can an addon render an unsaved entry through the site's own
   templates?~~ **Answered on the same day: yes.** The recipe and the three
   attempts it took to find it are the next entry. Struck rather than deleted, so
   the record shows this was the question the design hung on rather than
   suggesting nobody thought to ask.

---

## 2026-08-12: The corpus exists, and it covers more than the thing it replaces

The corpus described in the entry below is built and lives in `corpus/`, here and
upstream, byte-identical.

**19 cases, all 17 rules.** The characterisation test it replaces as the fork's
safety net pinned five. The other twelve could have drifted between the two
projects without a test going red, which means the arrangement this fork depends
on would have been guarding a third of what it claimed. Both suites now fail if
any rule has no case.

**Pinned:** the rule, the severity, the label it may cite, and the order findings
come back in. **Not pinned:** message and call to action, which are wording, and
anything carrying a block uid or field key, which only the other renderer can produce. A
corpus demanding those would be unshareable by construction.

**Every case declares its portability, and the declaration is checked against the
fixture rather than trusted.** Six of the seventeen rules read stamped
attributes only the other renderer produces. A case whose HTML carries them while
claiming to be portable fails the suite. There is a third value, `host-styling`,
for the one rule that is portable code but only fires on inline styles: neither
"works everywhere" nor "needs our markup", and calling it either would be a lie in
one direction.

**The limit, stated rather than discovered.** The expectations were generated by
running the upstream checker as it stands, not written from what it should do. So
the corpus cannot catch a bug already shipping in both projects. It is not for
that. It is for catching the two projects answering differently, and for that the
starting point only has to be shared.

**Mutation-tested, five run, five killed:** a blocking rule downgraded to a
warning, the pack reordered, a family deleted from the pack, a WCAG number cited
that no check can establish, and the corpus directory hidden. The last is the one
that mattered: a loop over a missing directory is a vacuous pass, and a vacuous
pass is the worst available outcome for a test whose only job is catching silent
divergence.

One mutation survived first time and the test was not at fault: it edited a
docblock while the real label lived elsewhere. Re-run against the actual source,
it was killed. Worth recording because a mutation that does not mutate proves
nothing, and reporting it as a survivor would have sent somebody to fix a test
that was fine.

**Not enforced here yet.** This repository has no test suite, so the corpus sits
unenforced and the README says so. The first code written here runs it.

---

## 2026-08-12: Two projects, forked, with one shared corpus holding them honest

**Decision.** This addon is its own codebase, maintained separately from upstream.
Not a shared Composer package with two consumers. Owner's call, made explicitly.

This closes open question 2, and it closes it against what the upstream plan
originally called the only acceptable answer. That plan said two copies of the
rules would drift, and drift is worse than not shipping, because a conformance
claim then depends on which copy ran. **That risk is real and is not waved away
here.** What changed is the weight on the other side of it.

**Why a fork is defensible, and it is not just convenience.**

The two are already different products, and a shared package would have had to
pretend otherwise. Upstream runs seven check families; this addon can run four,
because three read attributes only that renderer stamps. Upstream maps a rule to
an editor field through `FieldTarget`; Statamic has no such fields. Its
gate blocks a publish it fully controls; this one hangs off `EntrySaving` and has
to infer what publishing even means. A single package serving both is a lowest
common denominator with two sets of escape hatches.

The extraction was also the most expensive and riskiest phase in the plan, and it
sat inside the codebase that pays the bills. A fork deletes that phase entirely.

And the release friction was permanent: a rule fix would have meant a package
release plus a version bump in two consumers, forever, on the thing upstream is
sold on. The standard fix for that is a monorepo with automated read-only splits,
whose tooling is a GitHub Action, and Actions on that repo are dead until
September.

**The mitigation, which is the actual decision here.** Forking the code is fine.
Forking the *behaviour* silently is not. So the two projects share a **conformance
corpus**: a directory of HTML fixtures and the exact findings each must produce,
checked into both, run by both suites. Implementations may diverge. Behaviour
diverges only when somebody changes the corpus, in a commit, on purpose.

That corpus already half exists, as the upstream
`StaticCheckerCharacterisationTest`, which pins the ordered rule list, all
fourteen keys of a violation, every severity, and the exact blocking set. It was
written to make a refactor safe and it turns out to be the thing that makes a
fork safe too.

**Rejected.** *One package, two consumers*, for the reasons above. *A fork with
no shared corpus*, which is the version of this decision that earns the original
warning: two accessibility tools with the same name and quietly different
answers.

**A consequence worth naming, because it is new.** Forking means publishing a
copy of the upstream checker, and that repository is private. The rules become
public. That is consistent with the decision above and with the fact that they
are readable in any element inspector, but it is a real change in exposure and it
should be a choice rather than a side effect.

---

## 2026-08-12: Source-available and proprietary, modelled on Statamic's own

**Decision.** A bespoke source-available licence, written plain, in `LICENSE.md`.
Not MIT, not BSL, not PolyForm.

**Why.** The fork decision above collapsed this question. There is no longer a
shared package needing a licence that lets a private commercial product and a
public paid one both use it. There is one codebase, owned outright, and the only
question left is what other people may do with source they can read.

The model is Statamic's own licence, which is five plain conditions: one
production install per licence, do not circumvent the licensing features, no
reuse in other products, keep the notice, follow the law. Copying that shape is
right for three reasons. It is the norm every buyer in this marketplace has
already agreed to once. It is readable by a procurement officer without a lawyer,
which matters when the buyer is a university or a hospital. And it permits
unlimited local and CI use, which is what makes "try before you buy" true rather
than a slogan.

**Rejected.** *MIT*, which permits anyone to resell this verbatim, and would be
the correct choice only if the goal were adoption rather than revenue. *Business
Source License*, whose time-delayed conversion to open source solves a problem
this product does not have and adds a clause every buyer would have to read
twice. *PolyForm Shield*, which is well drafted and standard, and which loses to
Statamic's licence only because matching the ecosystem is worth more here than
matching a standard nobody in it uses.

**The clause that is not boilerplate.** The licence states outright that the
Software cannot tell anyone a site is accessible, that nothing it produces is a
conformance claim, and that a page it allows has not been proven clean. That is
the same refusal the README and `CLAUDE.md` already make, put where it survives
being resold, forked, or quoted back during a dispute. For a tool bought to
discharge a legal obligation, the disclaimer is a product feature.

**Not done.** No lawyer has read it. One should, before the first sale, and the
conformance disclaimer is the clause most likely to be tested.

---

## 2026-08-12: Public, and sold anyway

**Decision.** This repository goes public and the addon is listed on the
Marketplace. That reverses "Private first" below, which is left in place rather
than edited, because the reasoning in it was sound and only its premise changed.

**Why the reversal is not a mistake being corrected.** "Private first" rested on
one stated unknown: "the question of whether a listed addon must have a public
repository is itself unanswered". It is answered now, and the answer is yes. A
decision that named the fact that would overturn it, and was overturned by
exactly that fact, worked as intended.

**Why public is right even setting the Marketplace aside.** Every rule this
addon enforces is visible in any browser's element inspector, because the whole
product reads rendered HTML. There is no algorithm here to protect. Keeping the
source closed would defend nothing while costing the two things that actually
sell a compliance tool: an auditor being able to read what it checks, and a buyer
being able to see it is maintained.

**What is actually being sold**, stated plainly so pricing never drifts from it:
the listing, the updates, the support, and a defensible answer when somebody asks
where the accessibility tool came from. Not access to the code.

> **Revisited on the same day, with numbers.** A later entry counts the market
> this paragraph assumes: paid Statamic addons install in the tens, and a direct
> competitor listed five weeks ago at $35 already ships the browser pass and the
> template-versus-content split. The reasoning above is still right about *what*
> is sold; it is wrong about how much that is worth. See "What the market
> actually looks like". Statamic's
licence check is a control-panel banner and nothing more, verified in source, so
any plan that assumes the code is withheld was never going to work.

**Rejected.** *Stay private and skip the Marketplace*, which forfeits both dogfood
sites' upgrade path along with the revenue: an addon installed from a private
Composer path repository is one nobody else can be handed. *Split the addon
public and the checker private behind a paid registry*, which is the most work,
makes the licence question harder rather than easier, and protects a set of rules
that can be read off the rendered page regardless.

**Not done yet, deliberately.** The repository is still private today. Publishing
it now would publish commercial reasoning and half-formed positioning with no
code to justify either. Nothing needs it public until the package is listed on
packagist.org, so the flip happens when there is something to install.

---

## 2026-08-12: What the Marketplace actually requires, read rather than assumed

Open question 3, answered from Statamic's own pages and from the installed
source at `statamic/cms v6.27.1`. The commercial half first, because one finding
contradicts a decision already taken in this file.

**The money.** 75% commission on every sale, paid to a connected Stripe account
at the time of purchase. The creator must live in a Stripe-supported country;
Statamic names India, Russia, Ukraine and China as currently excluded. Prices
and editions are set by the seller and changeable at any time. No review or
approval process is documented anywhere, which means listing is self-serve and
the quality bar is whatever the seller enforces.

**Distribution, and this is the finding that matters.** Selling requires the
package to be published on packagist.org, which is stated twice: "Publish your
Composer package on packagist.org" and "Publish your Starter Kits and Addons by
linking to GitHub (and Packagist for addons)." Packagist serves public
repositories. **A Marketplace addon therefore cannot have a private source
repository**, which directly contradicts this file's earlier private-first
decision. That decision now needs revisiting rather than quietly surviving.

**Licence enforcement, which is weaker than the word "licence" suggests.**
Statamic's own documentation says: "You can try out commercial addons locally for
free. Be sure to purchase a license before deploying to production." Checked
against the source rather than taken on trust: `LicenseManager` validity reaches
the control panel through `HandleAuthenticatedInertiaRequests::licensing()` as an
`alert` key, and nothing in `src/` aborts, throws, or gates a feature on it. An
unlicensed addon runs. The buyer sees a banner.

So the code is public, the install is unimpeded, and the licence is a banner.
**What is being sold is not access to the code.** It is the listing, the updates,
and the fact that a team with a compliance obligation would rather pay than
explain to their auditor why they vendored an unlicensed accessibility tool. Any
pricing or positioning that assumes the code is withheld is wrong.

The upside of the same fact: `"editions": ["free", "pro"]` in composer.json plus
`Addon::get(...)->edition()` gives a free tier for nothing, and Statamic says
"You don't need to check whether a license is valid, Statamic does that
automatically for you."

---

## 2026-08-12: The gate can refuse a save, verified by running it

The second thing the product depends on, after rendering. Not read: run, against
the same site.

`Statamic\Entries\Entry::save()` dispatches `EntrySaving` and returns early when
a listener returns `false` (`src/Entries/Entry.php:415`). Confirmed live: `save()`
returned `false` and the title on disk was unchanged.

Listeners in an addon's `src/Listeners` directory auto-register from the type hint
alone, so the whole gate is one class.

**The gap, named now rather than discovered in support.** The control panel's
save endpoint returns `'saved' => $saved`, a bare boolean with no message
(`EntriesController`). A refusal with no reason attached is exactly the failure
this product exists to prevent, and it would read as the editor being broken.
The compiled control-panel JavaScript is not shipped in the vendor package, so
what an author actually sees on a `false` save is **unverified**. Finding that
out, and deciding between returning false and throwing a validation exception
that carries the violations, is the first real implementation question.

**Also worth knowing:** there is no `EntryPublishing` event. The list is
Creating, Saving, Saved, Created, Deleting, Deleted, ScheduleReached. So the gate
hangs off saving and has to decide for itself what counts as publishing, which
is where a warn mode and a "only when published" setting will actually live.

---

## 2026-08-12: The control panel UI is a documented component library

`https://ui.statamic.dev` is a Storybook of 54 components, and the ones this
addon needs already exist: `Alert`, `Badge`, `StatusIndicator`, `Panel`,
`Listing`, `EmptyState`, `ConfirmationModal`, `Tooltip`.

This removes a whole category of work and a whole category of risk. A
hand-rolled violations panel would be a bespoke widget inside an accessibility
product, which is the worst possible place to invent one. Use the kit, and any
accessibility defect in the panel is Statamic's to fix rather than ours to have
shipped.

The Storybook is JavaScript-rendered, so its pages cannot be read by fetching
HTML. Read `index.json` for the component list, or read the installed package.

---

## 2026-08-12: An unsaved entry can be rendered, and here is exactly how

**Answered by running it** against a real Statamic 6 / Laravel 13 site
(hada.farm), not by reading documentation. This was the spike that gated the
whole design.

**The recipe, three lines:**

```php
$entry->setSupplement($field, $unsavedValue);  // not set(): set() is not what Live Preview uses
$entry->repository()->substitute($entry);      // so lookups during render return THIS instance
$html = $entry->toResponse($request)->getContent();
```

Verified: the unsaved value appears in the rendered HTML, 27,960 bytes came back
through the site's own templates, and the entry on disk was untouched.

**Why each line is needed, learned by watching it fail.**

`set()` mutates the entry and reaches augmentation correctly, and the rendered
page still showed the SAVED value. `toResponse` resolves the entry again during
rendering rather than trusting the instance it was called on.

`setSupplement` is what Statamic's own `PreviewController::edit` uses to apply
unsaved form values, so it is the supported shape rather than a trick.

`substitute()` is the missing piece, and it is lifted from
`Statamic\Tokens\Handlers\LivePreview`, which does exactly this when a Live
Preview token arrives. Registering the instance with its repository is what makes
the re-resolution during rendering return the modified entry.

**No token, no cache, no CP request needed.** Live Preview wraps this in a token
so a browser can request the front end, but the addon runs inside the same
process as the save, so it can substitute directly and skip all of that.

**A trap worth writing down, because it cost most of the spike.** The first three
attempts reported failure against a page whose template is a hardcoded landing
page that never outputs `{{ title }}`. The mechanism was working by attempt two
and the fixture could not show it. Any test of this must use an entry whose
template actually renders the field being changed, and should assert the
mechanism against a field it has confirmed appears in the output.

**What this does not yet prove:** that rendering is safe to do inside a save
lifecycle hook, that it is fast enough to sit in front of a publish, and what
happens when a template throws for a half-complete entry. Those are the next
questions, and none of them are blocking in the way this one was.

---

## 2026-08-12: Named for what it does, not for who made it

**Decision.** `statamic-a11y-gate`, and the Marketplace listing reads
"Accessibility Gate" rather than carrying the parent product's name.

**Why.** A buyer evaluating this has a compliance problem, not an opinion about
it. A name that requires knowing the parent product spends attention on the
wrong thing, and it ties a standalone tool's reputation to a CMS the buyer has
already decided not to use.

**Rejected.** A name built from the parent product's, which would build that name with
Statamic developers. That is a real benefit and it was turned down because the
addon is a product rather than an advertisement, and because the two can be
associated in the listing copy without being welded together in the package name.
*`publish-gate`*, ownable as a brand across Craft and a build CLI later, and
turned down because a buyer would have to read the description to learn it is
about accessibility at all.

---

## 2026-08-12: Private first

> **Superseded the same day by "Public, and sold anyway".** The open question this
> entry named as unresolved got resolved, and it resolved against this decision.
> Kept rather than deleted: a log that only shows decisions which survived teaches
> nothing about how they were made.

**Decision.** The repository starts private and can be opened later.

**Why.** The direction is one-way in practice. A public history stays cached and
forked after a repository is flipped back, and the early commits of this project
will contain half-formed decisions about a product being sold. Nothing about the
Marketplace is blocked by starting private, and the question of whether a listed
addon must have a public repository is itself unanswered (see open questions).

**Rejected.** *Public from the first commit*, which is the better default for a
library nobody pays for and the wrong one for a commercial addon whose core is
extracted from a private codebase.
