# The conformance corpus

The same web pages, the same expected answers, checked into two repositories that
share no code.

Windrow and the Statamic addon (`bpmore/statamic-a11y-gate`) are deliberate forks
of the same accessibility rules, maintained separately. Forking the code is
fine. Forking the *answers* is not: two checkers that quietly disagree mean "did
this page pass?" depends on which one ran, and that is the one thing a tool sold
on conformance cannot afford.

This directory is how that is prevented. Both projects run it. Both must agree.

## The rule

**Behaviour diverges only by editing this corpus, in its own commit, with the
reason written down.** A rule change that shifts a finding and does not touch the
corpus is a defect, whichever project it happens in.

## What a case looks like

```
cases/heading-skipped-level/
  input.html      the page under check
  expected.json   what both projects must report
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
order findings come back in. Order counts because an editor renders its issue
list in the order it receives, so the same findings in a different sequence show
an author something different first.

**Not pinned:** the message and the call to action. Those are wording, and
wording must stay free to improve. Also not pinned: anything carrying a block
id, field key, or breakpoint, because only Windrow can produce those and a
corpus that demanded them would be unshareable by construction.

This mirrors how the operator fingerprints a finding: on the evidence, not the
prose.

## portability, and why every case declares it

Six of the seventeen rules read `data-windrow-*` attributes that only Windrow's
renderer stamps. In any other host they find nothing, and **a check that silently
finds nothing is indistinguishable from a check that passed.** That is the worst
failure available to this product, so the corpus makes each case say which it is:

| Value | Meaning |
|---|---|
| `portable` | reads nothing but ordinary HTML. Must behave identically in every host. |
| `host-markup` | needs `data-windrow-*` attributes the host has to stamp. Finds nothing without them, and that is expected. |
| `host-styling` | portable code, but only fires on inline styles. See below. |

`host-styling` exists for one case and is the subtlest of the three.
`target-size-minimum` reads the inline `style` attribute. The code is perfectly
portable, and a site that sizes its buttons in a stylesheet will never trigger
it. So it is neither "works everywhere" nor "needs our markup", and calling it
either would be a lie in one direction or the other.

A case whose HTML contains `data-windrow-` and claims to be `portable` fails the
suite. The label is checked against the fixture, not trusted.

## How these expectations were produced

**By running the checker that exists today, not by writing down what it should
do.** That makes this a characterisation corpus, and it has a limit worth stating
plainly: *it cannot catch a bug that is already shipping in both projects.* If a
rule is wrong today, the corpus faithfully records it as wrong.

That is not what it is for. It is for catching the two projects drifting apart,
and for that the starting point only has to be shared, not correct. Rules being
right is the job of each project's own rule tests, which check that a rule fires
for the right reason. This checks that both projects answer the same.

## Coverage

19 cases covering all 17 rules the check pack can raise, plus a clean page and a
multi-defect page that pins cross-family ordering.

The suite fails if a rule has no case. That assertion is the one that keeps this
honest as rules are added: a new rule with no fixture is a rule the fork is
unguarded for.

## Adding a case

1. Add `cases/<name>/input.html`.
2. Add `cases/<name>/expected.json` with `name`, `why`, `portability`, `findings`.
3. Run both suites.
4. Copy the case into the other repository in the same change.

Write `why` for somebody who has not read the rule. It is the only explanation
the other project's maintainer will have.
