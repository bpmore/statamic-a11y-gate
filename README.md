# Accessibility Gate for Statamic

An entry that fails WCAG 2.2 AA cannot be published.

Not "here is a report". The publish is refused, in the control panel, with plain
language saying what is wrong and what to do about it.

## Status

Nothing works yet. This repo was created on 2026-08-12 and currently holds
decisions, not code. The design lives in the Windrow repo at
`docs/plans/statamic-addon.md`.

**`corpus/` is here and is not yet enforced**, because there is no test suite to
enforce it with. It is the shared conformance corpus: 19 pages and the exact
findings this addon must produce for each, identical to the copy in Windrow.
Stating that it is unenforced rather than letting its presence imply otherwise is
the same rule this product applies to its own checks, and it would be a poor
start to break it in the README.

The first code written here runs it.

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

Undecided. See `docs/DECISIONS.md`.
