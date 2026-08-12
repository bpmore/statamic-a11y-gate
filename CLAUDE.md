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

## THE CONFORMANCE CORPUS IS THE CONTRACT

This codebase is a deliberate fork of Windrow's checker, maintained separately.
The one thing that must not fork is the answers.

- A directory of HTML fixtures and the exact findings each must produce lives in
  both projects and is run by both suites. Implementations may diverge freely.
- **Behaviour diverges only by editing the corpus, in its own commit, with the
  reason written down.** A rule change that alters a finding and does not touch
  the corpus is a defect, whichever project it happens in.
- Adding a check means adding its fixtures first. A check with no corpus entry is
  a check the other project cannot be held to.
- When the two projects genuinely must differ, say so in the corpus rather than
  in silence. A fixture may be marked as expected-to-differ with the reason. An
  undocumented disagreement is the failure this whole arrangement exists to
  prevent.

## THE RULES ARE NOT COPIED

- The check rules come from one place. If this repo ever contains a second
  implementation of a rule that Windrow also has, they will drift, and a
  conformance claim that depends on which copy ran is worse than no addon.
- Where that one place lives is an open question in `docs/DECISIONS.md`. Do not
  resolve it by writing rules here.

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
- Statamic's licence check is a control-panel banner, not a block, verified in
  source. Never write copy, pricing or code that assumes an unlicensed install is
  prevented. It is not.
- What is sold is the listing, the updates, the support and a defensible answer
  about where the tool came from. Not access to the code.

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
- The design lives in the Windrow repo at `docs/plans/statamic-addon.md`. If this
  repo and that plan disagree, stop and ask rather than picking one.
