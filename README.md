# Accessibility Gate for Statamic

An entry that fails WCAG 2.2 AA cannot be published.

Not "here is a report". The publish is refused, in the control panel, with plain
language saying what is wrong and what to do about it.

## Status

**The gate works and the panel is built.** Install this addon and a save that
would leave a published entry with an accessibility error is refused, with the
reason attached. Add the Accessibility field to a blueprint and an author can
check the page before pressing publish, including changes they have not saved.

**Seen in a real control panel**, on hada.farm: the field renders in the entry
sidebar. What has not been watched yet is the round trip, an author pressing
Check this page and the findings coming back.

**`corpus/` is now enforced.** It is the shared conformance corpus: 19 pages and
the exact findings this project must produce for each, identical to the copy in
Windrow. `composer test` runs it, and the suite fails if any rule has no case.

Still to come: reporting which of the seven check families could run at all. The
design lives in the Windrow repo at `docs/plans/statamic-addon.md`.

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

Four checks read only the rendered HTML and work on any site: heading order, link
purpose, image alt text, and target size.

Three more need the site to mark up what it knows: whether a video has captions,
whether a link points somewhere unpublished, and the reading grade of a plain
language summary. Those are opt-in and documented, not magic.

A browser pass with axe catches more than any of this. That is stated here rather
than left for a buyer to discover.

## Licence

Source-available and proprietary, modelled on Statamic's own. Read it in
`LICENSE.md`: it is five plain conditions and one clause that is not
boilerplate, which says outright that nothing this software produces is a
conformance claim.
