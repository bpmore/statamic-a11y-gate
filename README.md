# Accessibility Gate for Statamic

An entry that fails WCAG 2.2 AA cannot be published.

Not "here is a report". The publish is refused, in the control panel, with plain
language saying what is wrong and what to do about it.

## Status

**The checker works. The addon does not exist yet.** There is no service
provider, no listener, and nothing to install into a Statamic site. What is here
is the engine: HTML in, findings out, framework-free, with the shared conformance
corpus enforced against it.

**`corpus/` is now enforced.** It is the shared conformance corpus: 19 pages and
the exact findings this project must produce for each, identical to the copy in
Windrow. `composer test` runs it, and the suite fails if any rule has no case.

Still to come, in the order the design calls for: reporting which checks could
not run, then the `EntrySaving` gate and the control-panel panel. The design
lives in the Windrow repo at `docs/plans/statamic-addon.md`.

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
