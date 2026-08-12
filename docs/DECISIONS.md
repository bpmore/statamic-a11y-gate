# Decision log

The **why**, and the **alternatives that were rejected**, behind choices that are
not obvious from the code they produced.

Newest first, headed `## YYYY-MM-DD: what was settled`, with a `---` between
entries. Same shape as the Windrow repo's log, deliberately: this project will be
read alongside that one.

---

## 2026-08-12: Open questions, listed rather than assumed

Not decisions. Things that must be answered before they are decided by accident.

1. **Licence.** Undecided, and it gates the repo layout. If the checker is shared
   with Windrow and this addon is paid, the licence has to permit that
   arrangement. Answer before the first line of shared code.
2. **Where the checker lives.** Windrow's repo is private and an addon must be
   installable. Either the checker becomes its own package, or this repo carries
   its own copy, and the second option is the one the plan explicitly forbids
   because two copies of the rules drift.
3. **Marketplace requirements.** Listing rules, pricing mechanics, licence-key
   enforcement, and whether a paid addon's repository must be public. To be read
   from Statamic's own documentation, not assumed.
4. **The spike.** Can an addon render an unsaved entry through the site's own
   templates? Everything else assumes it can. One afternoon, and if the answer is
   no the design changes shape.

---

## 2026-08-12: Named for what it does, not for who made it

**Decision.** `statamic-a11y-gate`, and the Marketplace listing reads
"Accessibility Gate" rather than carrying the Windrow name.

**Why.** A buyer evaluating this has a compliance problem, not an opinion about
Windrow. A name that requires knowing the parent product spends attention on the
wrong thing, and it ties a standalone tool's reputation to a CMS the buyer has
already decided not to use.

**Rejected.** *`windrow-statamic-gate`*, which would build the Windrow name with
Statamic developers. That is a real benefit and it was turned down because the
addon is a product rather than an advertisement, and because the two can be
associated in the listing copy without being welded together in the package name.
*`publish-gate`*, ownable as a brand across Craft and a build CLI later, and
turned down because a buyer would have to read the description to learn it is
about accessibility at all.

---

## 2026-08-12: Private first

**Decision.** The repository starts private and can be opened later.

**Why.** The direction is one-way in practice. A public history stays cached and
forked after a repository is flipped back, and the early commits of this project
will contain half-formed decisions about a product being sold. Nothing about the
Marketplace is blocked by starting private, and the question of whether a listed
addon must have a public repository is itself unanswered (see open questions).

**Rejected.** *Public from the first commit*, which is the better default for a
library nobody pays for and the wrong one for a commercial addon whose core is
extracted from a private codebase.
