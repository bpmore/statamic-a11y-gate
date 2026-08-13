# CLAUDE.md

Non-negotiables for any model working in this repository. Rules, not
documentation. Obey them without asking for confirmation.

This is a commercial addon whose entire value is that its claims are true. Most
rules here exist to protect that.

## THE PRODUCT

- The gate REFUSES a publish. It does not report, score, or advise. If a change
  would turn a refusal into a warning, stop and ask.
- Never claim conformance. The addon says what ran and what it found. "Compliant",
  "fully accessible", and any percentage are forbidden in code, copy, and output.
- Never cite a WCAG success criterion a check cannot establish. A house rule gets
  a plain name, not a number.
- A check that could not run must say so. A silent zero is indistinguishable from
  a pass, and shipping that would make every other claim here worthless.

## THE CORPUS IS THE CONTRACT

Every rule has at least one fixed page whose answer is written down, in
`corpus/`. That is what stops a rule changing what it reports by accident.

- **Behaviour changes only by editing the corpus, in its own commit, with the
  reason written down.** A rule change that alters a finding and does not touch
  the corpus is a defect.
- Adding a check means adding its fixtures first. The suite fails if a rule has
  no case, so a new rule with nothing pinned cannot ship quietly.
- The corpus records what the checker does, not what it ought to do. It cannot
  catch a bug that is already shipping. Whether a rule is right is the job of the
  rule's own tests; this catches a change nobody meant to make.

This directory was once shared with another project, so that two implementations
of the same rules could be held to the same answers. That ended when this addon
was separated to stand on its own. The corpus stayed, because what it was worth
day to day never depended on anyone else running it.

## THE RULES LIVE HERE, AND NOWHERE ELSE

This project owns its rules outright. There is no upstream to port from and no
second implementation to stay in step with.

- That was not always true, and the history is in `docs/DECISIONS.md`. It matters
  only if you find something that looks like it was written to match somebody
  else's shape. It was. It does not have to any more.
- The danger the old arrangement managed has not disappeared, it has moved: there
  is now nothing outside this repository that will notice a rule quietly changing
  its mind. The corpus is the only thing that will, which is why the rule above is
  the first one in this file that is not about the product.

## STACK

- PHP 8.4. Composer. PSR-4. A Statamic addon is an ordinary Laravel package.
- The checker itself must stay framework-free: no Laravel container, no Eloquent,
  no facades in the rules. It takes HTML and returns findings.
- Statamic 6, verified against `statamic/cms v6.27.1`. The gate hangs off
  `EntrySaving`, which halts the save when a listener returns `false`. There is
  no `EntryPublishing` event, so the addon decides for itself what counts as
  publishing.
- Control-panel UI comes from Statamic's own component library at
  `ui.statamic.dev`. Never hand-roll a widget: a bespoke control inside an
  accessibility product is the worst possible place to invent one.

## THIS REPOSITORY IS GOING PUBLIC

Write every commit, comment and document as though a customer, a competitor and
an auditor will all read it, because once this is listed they can.

- The rules are readable in any element inspector anyway. Nothing here is
  protected by being unpublished, so nothing should be written as if it were.
- **The addon is free. No price, no licence key, no per-site limit, no edition.**
  Never write copy, a setting, or a code path that implies otherwise, and never
  add a licence check: there is nothing to check.
- **The licence is still proprietary, and the no-reuse clause is the point.**
  These rules began as a fork of the accessibility engine behind another product
  by the same author. A permissive licence would give that engine away. Free to
  use is the intent. Free to take is not.
- **The other product is never named here.** Not in copy, not in a comment, not
  in an attribute a site owner has to type. It is listed as "Accessibility Gate"
  under the author's own name, and somebody evaluating it should not need to know
  anything else exists. The only exception is `docs/DECISIONS.md`, which is a
  record rather than a pitch.

## TESTING

- Mutation-test a check before trusting it: break the thing it guards and confirm
  the check fails. A test that passes against broken code is not a test.
- `expect($x)->not->toContain($needle, "message")` is vacuously true. `toContain`
  takes a list of needles, not a failure message. Same for `toBeNull()`. Use
  `in_array(...)` or a boolean with `toBeTrue($message)`.
- A loop over a possibly-empty array asserts nothing. Assert the empty case
  directly.

## WRITING

- No em dashes anywhere: not in code comments, commit messages, docs, or
  user-facing copy. Use colons, commas, parentheses, or two sentences.
- Comments say why, and name the failure that motivated the rule.
- Commit messages and PR bodies say why, and name what was checked and what was
  not.

## PROCESS

- Branch, work, run the tests, open a PR. Never push to main directly.
- Never push or merge unless asked.
- Read `docs/DECISIONS.md` before choosing an approach. Add an entry when your
  change settles a question, including the alternatives you turned down.
- There is no design document outside this repository any more. What this addon
  should do is settled here, in the decision log and in the corpus, and nowhere
  else has a vote.
