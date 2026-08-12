# Accessibility Gate for Statamic

An entry that fails WCAG 2.2 AA cannot be published.

Not "here is a report". The publish is refused, in the control panel, with plain
language saying what is wrong and what to do about it.

## Status

**The gate works and the panel is built.** Install this addon and a save that
would leave a published entry with an accessibility error is refused, with the
reason attached. Add the Accessibility field to a blueprint and an author can
check the page before pressing publish, including changes they have not saved.

**Watched working in a real control panel**, on hada.farm: the field renders in
the entry sidebar, the button runs the check, and the result comes back.

**`corpus/` is now enforced.** It is the shared conformance corpus: 19 pages and
the exact findings this project must produce for each, identical to the copy in
Windrow. `composer test` runs it, and the suite fails if any rule has no case.

Every result says how much of the page it could see. The design lives in the
Windrow repo at `docs/plans/statamic-addon.md`.

## Adding the panel

It is a field. Put it wherever it belongs in a blueprint, usually the sidebar:

```yaml
-
  handle: a11y_panel
  field:
    type: accessibility_panel
    display: Accessibility
```

It stores nothing in the entry. Findings change every time the page does, so a
copy of them in the content directory would be wrong by the next save.

## Checking a whole site

```
php please a11y:check
```

Renders every published page and groups what it finds by how many pages each
problem is on.

```
Checked 38 pages.

Must be fixed before publishing

  every page   Use descriptive link text
    Link text like "click here" or a bare web address doesn't describe where the link goes.
    Read more

  1 page       Add a description
    This image has no description, so people using screen readers don't know what it shows.
    /img/nothing.jpg
    /blog/first-post
```

**A problem on one page is something somebody wrote. The same problem on every
page came from a template.** The first costs an author an afternoon. The second
is one fix worth every page at once, so it sorts to the top.

It exits non-zero when anything must be fixed, or when a page could not be
rendered at all, so it can stop a release. `--collection=blog` narrows it.
`--drafts` includes unpublished entries, which is what a starter kit author
wants before shipping.

## How it decides what counts as publishing

Statamic has no publishing event. The entry events are Creating, Saving, Saved,
Created, Deleting, Deleted and ScheduleReached, checked against
`statamic/cms v6.27.1`. So the addon decides for itself, and it decides on the
state the save would leave behind:

- **An entry that will be live when the save finishes is checked.** That covers
  the publish button as well as the save button, because Statamic's publish path
  sets published and calls save.
- **A draft is not checked.** Neither is an entry in a collection you did not ask
  it to gate, or one with no page of its own.
- **Re-saving a live page is checked**, deliberately. A page that a later edit
  breaks is exactly as broken as one published broken.
- **If the page cannot be rendered, the save is refused** and says so. A check
  that could not run is not a check that passed.

## What it does

- Renders the entry through the site's own templates and checks the HTML.
- Refuses the publish if anything at error severity is found.
- Says which checks ran and which could not, because a check that silently finds
  nothing looks exactly like a check that passed.

## What it refuses to do

- **No score out of a hundred.** Automated checking cannot support that number.
- **No conformance badge.** It says what ran and what it found. It never says a
  site is compliant.
- **No overlay, no toolbar, no widget.** Those move the problem to the visitor.
- **No silent degradation.** If a check could not run, the output says so.

## Honest limits

**The author is told two things and no more:** what to fix, and where the tool
could not see something in *their own content* that only they can settle. Today
that second one means one thing:

> Captions were not checked. Only you can confirm this video or recording has
> them.

A page with no video says nothing about captions, the same way a page with no
images says nothing about descriptions. There was nothing there to miss.

**Everything else lives on one page under Tools**, "Accessibility Gate". It says
what runs, what cannot run and why, which two checks a developer can switch on,
and what a clean result does not mean. The result panel does not link to it: an
author pressing a button is asking about their page, and somebody who wants to
know what the tool is worth goes and reads it once.

**What the author is never shown**, because they cannot act on it: which checks
ran and how far. That is an auditor's number. It stays in the data the check
endpoint returns, where a report can be built on it.

Two checks are off unless your templates opt in, and they are named in
`config/a11y-gate.php`:

- Links to a page that is not published yet.
- The reading grade of a plain-language summary.

Neither leaves a trace in the finished page, so neither can be checked without
the site marking it up. Findings still arrive either way: a page that carries the
markup is checked whether or not the setting mentions it.

A browser pass with axe catches more than any of this. That is stated here rather
than left for a buyer to discover.

## Licence

**Free.** No price, no licence key, no per-site limit. Run it on as many sites as
you like.

The licence is still proprietary, and `LICENSE.md` says why in the condition
itself: these rules are a fork of the accessibility engine behind another
product by the same author, so free to use is the intent and free to take is
not. Reading the code to learn from it is expected.

One clause is not boilerplate: nothing this software produces is a claim of
conformance with anything, and a page it allows has not been proven
accessible.
