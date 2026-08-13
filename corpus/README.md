# The corpus

Fixed pages, and the exact findings this checker must produce for each of them.

This started life as a shared artefact: the same fixtures checked into two
repositories so that two implementations of the same rules could be held to the
same answers. That arrangement ended when this addon was separated to stand on
its own, and the reasoning is in `docs/DECISIONS.md`. The corpus stayed, because
what it was worth day to day never depended on anyone else running it.

**What it is worth is this.** Every rule has at least one page whose answer is
written down, so a rule cannot change what it reports without a test saying so by
name. That caught a fix over-reaching twice in one afternoon, in cases written
minutes apart, which is the whole argument for keeping it.

## The rule

**Behaviour changes only by editing this corpus, in its own commit, with the
reason written down.** A rule change that shifts a finding and does not touch the
corpus is a defect.

Adding a check means adding its fixtures. The suite fails if a rule has no case,
so a new rule with nothing pinned cannot ship quietly.

## What a case looks like

```
cases/heading-skipped-level/
  input.html      the page under check
  expected.json   what this project must report
```

```json
{
  "name": "heading-skipped-level",
  "why": "A jump from h1 straight to h3, which breaks the outline a screen reader announces.",
  "portability": "portable",
  "findings": [
    { "rule": "heading-skipped-level", "severity": "error", "wcag": "Heading structure" }
  ]
}
```

## What is pinned, and what is deliberately not

**Pinned:** the rule, the severity, the label it is allowed to cite, and the
order findings come back in. Order counts because the panel renders its list in
the order it receives, so the same findings in a different sequence show an
author something different first.

**Not pinned:** the message and the call to action. Those are wording, and
wording must stay free to improve. Pinning prose would make every improvement to
a sentence look like a behaviour change, and the point of this directory is that
a behaviour change is impossible to miss.

## portability, and why every case declares it

Six of the seventeen rules read `data-a11y-*` attributes that a host has to stamp
into its own templates. Without them those rules find nothing, and **a check that
silently finds nothing is indistinguishable from a check that passed.** That is
the worst failure available to this product, so every case says which it is:

| Value | Meaning |
|---|---|
| `portable` | reads nothing but ordinary HTML. Must behave identically on every site. |
| `host-markup` | needs `data-a11y-*` attributes the site has to stamp. Finds nothing without them, and that is expected. |
| `host-styling` | portable code, but only fires on inline styles. See below. |

`host-styling` exists for one case and is the subtlest of the three.
`target-size-minimum` reads the inline `style` attribute. The code is perfectly
portable, and a site that sizes its buttons in a stylesheet will never trigger
it. So it is neither "works everywhere" nor "needs our markup", and calling it
either would be a lie in one direction or the other.

A case whose HTML contains `data-a11y-` and claims to be `portable` fails the
suite. The label is checked against the fixture, not trusted.

## How these expectations were produced

**By running the checker that exists today, not by writing down what it should
do.** That makes this a characterisation corpus, and it has a limit worth stating
plainly: *it cannot catch a bug that is already shipping.* If a rule is wrong
today, the corpus faithfully records it as wrong.

That is not what it is for. It is for catching a change nobody meant to make.
Whether a rule is right in the first place is the job of the rule's own tests,
which check that it fires for the right reason.

## Coverage

22 cases covering all 17 rules the check pack can raise, plus a clean page and a
multi-defect page that pins cross-family ordering.

## Adding a case

1. Add `cases/<name>/input.html`.
2. Add `cases/<name>/expected.json` with `name`, `why`, `portability`, `findings`.
3. Run the suite.

Write `why` for somebody who has not read the rule, including yourself in a year.
It is the only explanation attached to the answer.
