# Accessibility Gate for Statamic

An entry that fails an accessibility check this addon can run does not get
published.

Not "here is a report". The publish is refused, in the control panel, with
plain language about what is wrong and what to do about it.

## Status

The gate works and the panel is built. Install the addon and a save that would
leave a published entry with an accessibility error is refused, with the reason
attached. Add the Accessibility field to a blueprint and an author can check a
page before pressing publish, unsaved changes included. It also works on a page
that has never been saved at all.

I have watched it work in a real control panel, on hada.farm. The field renders
in the entry sidebar, the button runs the check, the result comes back.

The rules are pinned. `corpus/` holds 28 fixed pages and the exact findings
this project must produce for each of them. `composer test` runs it, and the
suite fails if a rule has no case behind it, so a rule cannot change what it
reports without a test going red.

Every result says how much of the page it could see.

## What the first hour looks like

Two things surprise people. Both are the addon doing its job.

A stock Statamic site cannot re-save its own home page. The default starter
template has no `h1`, so the gate refuses with "Add a heading". That is a real
finding about a real page, in a template you did not write. Fix the template,
or switch to reporting while you clear the ground.

The first save of a new page is not checked. On a collection that routes
through the page tree, an entry has no address until it is saved, so there is
nothing to fetch yet. The panel says so, and every save after the first is
checked normally.

The gate ships on for every collection, refusing. If that is too much for the
site you are installing on, switch it to report instead. The setting is in the
addon's settings screen, and a site with a backlog is what reporting is for.

## The panel

It is already there. The panel appears in the sidebar of every checked
collection that has pages, no blueprint edits needed. "Add the panel to the
collections above" in the addon's settings turns it off again.

This uses the same mechanism Statamic uses for `slug` and `date`, which are not
in your yaml either. The field behaves like a native one and leaves nothing
behind if the addon is removed. It is on by default because the gate refuses
publishes from the moment the addon is installed, and this panel is the only
place those findings are readable. Turn it off if you want, but then the field
needs to live somewhere or a refusal cannot be read.

To place it yourself, put it wherever it belongs in a blueprint:

```yaml
-
  handle: a11y_panel
  field:
    type: accessibility_panel
    display: Accessibility
```

Your placement wins. A blueprint that already has the field does not get a
second copy.

The field stores nothing in the entry. Findings change every time the page
does, so a copy of them in the content directory would be stale by the next
save.

## Settings

**Addons > Accessibility Gate > Settings**, in the control panel.

- What happens when a page has a problem: refuse the publish, or report it and
  publish anyway.
- Which collections to check.
- Whether to add the panel to entry forms automatically.
- The two checks that need your templates to mark something up first.

Everything is also in `config/statamic-a11y-gate.php` if you prefer a file. The
screen wins once somebody saves it; the file answers until then. A settings
screen that silently loses to a file is worse than no screen at all.

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

A problem on one page is something somebody wrote. The same problem on every
page came from a template. The first costs an author an afternoon. The second
is one fix worth every page at once, so it sorts to the top.

The command exits non-zero when anything must be fixed, or when a page could
not be rendered at all, so it can stop a release. `--collection=blog` narrows
it. `--drafts` includes unpublished entries, which is what a starter kit author
wants before shipping.

## How it decides what counts as publishing

Statamic has no publishing event. The entry events are Creating, Saving, Saved,
Created, Deleting, Deleted and ScheduleReached, checked against
`statamic/cms v6.27.1`. So the addon decides for itself, based on the state the
save would leave behind:

- An entry that will be live when the save finishes is checked. That covers the
  publish button as well as the save button, because Statamic's publish path
  sets published and calls save.
- A draft is not checked. Neither is an entry in a collection you did not ask
  it to gate, or one with no page of its own.
- Re-saving a live page is checked. A page that a later edit breaks is as
  broken as one published broken.
- If the page cannot be rendered, the save is refused and says so. A check that
  could not run is not a check that passed.

## What it does

- Renders the entry through the site's own templates and checks the HTML.
- Refuses the publish if anything at error severity is found.
- Says which checks ran and which could not.

## What it refuses to do

- No score out of a hundred. Automated checking cannot support that number.
- No conformance badge. It says what ran and what it found. It never says a
  site is compliant.
- No overlay, no toolbar, no widget. Those move the problem to the visitor.
- No silent degradation. If a check could not run, the output says so.

## Honest limits

The author is told two things and no more: what to fix, and where the tool
could not see something in their own content that only they can settle. Today
that second one means one thing:

> Captions were not checked. Only you can confirm this video or recording has
> them.

A page with no video says nothing about captions, the same way a page with no
images says nothing about descriptions. There was nothing there to miss.

Everything else lives on one page under Tools, "Accessibility Gate". It says
what runs, what cannot run and why, which two checks a developer can switch on,
and what a clean result does not mean. The result panel does not link to it. An
author pressing a button is asking about their page; somebody who wants to know
what the tool is worth goes and reads that page once.

One thing the author is never shown, because they cannot act on it: which
checks ran and how far. That is an auditor's number. It stays in the data the
check endpoint returns, where a report can be built on it.

Two checks are off unless your templates opt in, and they are listed in the
addon's settings:

- Links to a page that is not published yet.
- The reading grade of a plain-language summary.

Neither leaves a trace in the finished page, so neither can be checked without
the site marking it up. Findings still arrive either way: a page that carries
the markup is checked whether or not the setting mentions it.

A browser pass with axe catches more than any of this. Better you read that
here than discover it later.

## Licence

Free. No price, no licence key, no per-site limit. Run it on as many sites as
you like.

The licence is still proprietary, and `LICENSE.md` says why in the condition
itself: these rules are a fork of the accessibility engine behind another
product by the same author, so free to use is the intent and free to take is
not. Reading the code to learn from it is expected.

One clause is not boilerplate: nothing this software produces is a claim of
conformance with anything, and a page it allows has not been proven
accessible.
