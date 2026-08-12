# Decision log

The **why**, and the **alternatives that were rejected**, behind choices that are
not obvious from the code they produced.

Newest first, headed `## YYYY-MM-DD: what was settled`, with a `---` between
entries. Same shape as the Windrow repo's log, deliberately: this project will be
read alongside that one.

---

## 2026-08-12: The checker is here, the corpus is enforced, and one hole in it is now known

The first executable code in this repository. It is the checker and nothing else:
no service provider, no listener, nothing installable into a Statamic site yet.
The README says so.

**The corpus runs, and it is the reason this was the first thing built.** All 19
cases, all 17 rules, green. The loader and every assertion are close to a copy of
Windrow's `ConformanceCorpusTest`, deliberately: the point of a shared corpus is
that both suites hold their project to the same statements, and a tidier rewrite
on one side is how the two quietly stop asking the same question.

**The rules were ported verbatim, and the deletions are the interesting part.**
Windrow's `Violation` carries `blockUid`, `fieldKey` and `breakpoint` so its
editor can highlight the block and focus the control behind an issue. A Statamic
entry rendered through the site's own templates has none of those, so all three
could only ever be empty strings here. They are dropped, along with `FieldTarget`
and the `blockMeta()` walk that fed them. The corpus pins none of it, so this
costs no agreement: an always-empty field is just an invitation to write code
that pretends to read it.

Two `config()` reads became constructor arguments, because the checker must stay
framework-free. The link-text word lists are now `LinkTextVocabulary`, whose
defaults are Windrow's current config values byte for byte. That class is where
the two projects would drift first and most quietly, because `link-unclear` and
`link-vague` are the only rules whose verdict depends on a list rather than on
the markup. `AccessibilityStandard` lost its registry, its `extends` chain and
its axe tag lists, keeping the one field a check actually reads: target size, 24
at AA and 44 at AAA.

The `data-windrow-*` attribute names were **not** renamed, though nothing in
Statamic stamps them. The corpus fixtures use those names, and renaming them here
would fork the corpus to no purpose.

**Mutation-tested, eight run, eight killed, but not on the first attempt.**
Killed: the pack reordered, a family deleted from the pack, a blocking rule
downgraded to a warning, a WCAG number cited for a heading rule that cannot
establish one, the corpus directory hidden, the skipped-heading comparison moved
off by one.

**One survived, and it found a hole in the shared corpus rather than in this
port.** Deleting the entire `alt=""` clause from `ImageAltCheck`, so that only a
missing attribute is reported, left the corpus suite green. Every image fixture
in `corpus/` either omits the attribute or fills it in, so nothing reaches the
branch. `alt=""` is the distinction that matters most in practice: it is what an
editor saves before a description is written, and it is also how a genuinely
decorative image opts out. Both forks are unguarded for it today.

Closed here with `tests/Unit/ImageAltCheckTest.php`, which kills that mutation
and a second one that ignores the decorative opt-out. **That is the local fix,
not the real one.** The real one is a corpus case added to both repositories in
one change, and it has not been done. Written down rather than quietly patched,
because a gap covered on one side only is exactly the asymmetry the corpus
exists to prevent.

**`composer.json` declares `"type": "library"`, not `"type": "statamic-addon"`.**
There is no service provider for Statamic to discover, and a package that
announces itself as an addon while containing none would fail at the first
install. It flips in the commit that adds the provider.

**Rejected:** *starting with the `EntrySaving` listener*, which is the visible
half of the product and the half that cannot be trusted without this one.
Rendering an entry and refusing a save are both verified spikes; a checker whose
answers nobody has pinned is not.

---

## 2026-08-12: Open questions, listed rather than assumed

Not decisions. Things that must be answered before they are decided by accident.

1. ~~**Licence.**~~ **Settled: source-available and proprietary, modelled on
   Statamic's own.** See `LICENSE.md`. The question got easier twice: once when
   the repository went public, so the licence stopped protecting the source, and
   again when the fork removed any need for it to accommodate a shared package.
   Outstanding: a lawyer has not read it.
2. ~~**Where the checker lives.**~~ **Settled: this repo carries its own copy.**
   The option the Windrow plan forbade, chosen deliberately by the owner, with
   the drift risk answered by a shared conformance corpus rather than ignored.
   Reasoning in the entry below.
3. ~~**Marketplace requirements.**~~ **Read, and the answer changed question 1.**
   Selling requires a package on packagist.org, so the repository must be public.
   Licence enforcement is a banner, not a block. Details in the next entry.
4. ~~**The spike.** Can an addon render an unsaved entry through the site's own
   templates?~~ **Answered on the same day: yes.** The recipe and the three
   attempts it took to find it are the next entry. Struck rather than deleted, so
   the record shows this was the question the design hung on rather than
   suggesting nobody thought to ask.

---

## 2026-08-12: The corpus exists, and it covers more than the thing it replaces

The corpus described in the entry below is built and lives in `corpus/`, here and
in Windrow, byte-identical.

**19 cases, all 17 rules.** The characterisation test it replaces as the fork's
safety net pinned five. The other twelve could have drifted between the two
projects without a test going red, which means the arrangement this fork depends
on would have been guarding a third of what it claimed. Both suites now fail if
any rule has no case.

**Pinned:** the rule, the severity, the label it may cite, and the order findings
come back in. **Not pinned:** message and call to action, which are wording, and
anything carrying a block uid or field key, which only Windrow can produce. A
corpus demanding those would be unshareable by construction.

**Every case declares its portability, and the declaration is checked against the
fixture rather than trusted.** Six of the seventeen rules read `data-windrow-*`
attributes only Windrow stamps. A case whose HTML contains that prefix while
claiming to be portable fails the suite. There is a third value, `host-styling`,
for the one rule that is portable code but only fires on inline styles: neither
"works everywhere" nor "needs our markup", and calling it either would be a lie in
one direction.

**The limit, stated rather than discovered.** The expectations were generated by
running Windrow's checker as it stands, not written from what it should do. So
the corpus cannot catch a bug already shipping in both projects. It is not for
that. It is for catching the two projects answering differently, and for that the
starting point only has to be shared.

**Mutation-tested, five run, five killed:** a blocking rule downgraded to a
warning, the pack reordered, a family deleted from the pack, a WCAG number cited
that no check can establish, and the corpus directory hidden. The last is the one
that mattered: a loop over a missing directory is a vacuous pass, and a vacuous
pass is the worst available outcome for a test whose only job is catching silent
divergence.

One mutation survived first time and the test was not at fault: it edited a
docblock while the real label lived elsewhere. Re-run against the actual source,
it was killed. Worth recording because a mutation that does not mutate proves
nothing, and reporting it as a survivor would have sent somebody to fix a test
that was fine.

**Not enforced here yet.** This repository has no test suite, so the corpus sits
unenforced and the README says so. The first code written here runs it.

---

## 2026-08-12: Two projects, forked, with one shared corpus holding them honest

**Decision.** This addon is its own codebase, maintained separately from Windrow.
Not a shared Composer package with two consumers. Owner's call, made explicitly.

This closes open question 2, and it closes it against what the Windrow plan
originally called the only acceptable answer. That plan said two copies of the
rules would drift, and drift is worse than not shipping, because a conformance
claim then depends on which copy ran. **That risk is real and is not waved away
here.** What changed is the weight on the other side of it.

**Why a fork is defensible, and it is not just convenience.**

The two are already different products, and a shared package would have had to
pretend otherwise. Windrow runs seven check families; this addon can run four,
because three read attributes only Windrow's renderer stamps. Windrow maps a rule
to an editor field through `FieldTarget`; Statamic has no such fields. Windrow's
gate blocks a publish it fully controls; this one hangs off `EntrySaving` and has
to infer what publishing even means. A single package serving both is a lowest
common denominator with two sets of escape hatches.

The extraction was also the most expensive and riskiest phase in the plan, and it
sat inside the codebase that pays the bills. A fork deletes that phase entirely.

And the release friction was permanent: a rule fix would have meant a package
release plus a version bump in two consumers, forever, on the thing Windrow is
sold on. The standard fix for that is a monorepo with automated read-only splits,
whose tooling is a GitHub Action, and Actions on the Windrow repo are dead until
September.

**The mitigation, which is the actual decision here.** Forking the code is fine.
Forking the *behaviour* silently is not. So the two projects share a **conformance
corpus**: a directory of HTML fixtures and the exact findings each must produce,
checked into both, run by both suites. Implementations may diverge. Behaviour
diverges only when somebody changes the corpus, in a commit, on purpose.

That corpus already half exists, as Windrow's
`StaticCheckerCharacterisationTest`, which pins the ordered rule list, all
fourteen keys of a violation, every severity, and the exact blocking set. It was
written to make a refactor safe and it turns out to be the thing that makes a
fork safe too.

**Rejected.** *One package, two consumers*, for the reasons above. *A fork with
no shared corpus*, which is the version of this decision that earns the original
warning: two accessibility tools with the same name and quietly different
answers.

**A consequence worth naming, because it is new.** Forking means publishing a
copy of Windrow's checker, and Windrow's repository is private. The rules become
public. That is consistent with the decision above and with the fact that they
are readable in any element inspector, but it is a real change in exposure and it
should be a choice rather than a side effect.

---

## 2026-08-12: Source-available and proprietary, modelled on Statamic's own

**Decision.** A bespoke source-available licence, written plain, in `LICENSE.md`.
Not MIT, not BSL, not PolyForm.

**Why.** The fork decision above collapsed this question. There is no longer a
shared package needing a licence that lets a private commercial product and a
public paid one both use it. There is one codebase, owned outright, and the only
question left is what other people may do with source they can read.

The model is Statamic's own licence, which is five plain conditions: one
production install per licence, do not circumvent the licensing features, no
reuse in other products, keep the notice, follow the law. Copying that shape is
right for three reasons. It is the norm every buyer in this marketplace has
already agreed to once. It is readable by a procurement officer without a lawyer,
which matters when the buyer is a university or a hospital. And it permits
unlimited local and CI use, which is what makes "try before you buy" true rather
than a slogan.

**Rejected.** *MIT*, which permits anyone to resell this verbatim, and would be
the correct choice only if the goal were adoption rather than revenue. *Business
Source License*, whose time-delayed conversion to open source solves a problem
this product does not have and adds a clause every buyer would have to read
twice. *PolyForm Shield*, which is well drafted and standard, and which loses to
Statamic's licence only because matching the ecosystem is worth more here than
matching a standard nobody in it uses.

**The clause that is not boilerplate.** The licence states outright that the
Software cannot tell anyone a site is accessible, that nothing it produces is a
conformance claim, and that a page it allows has not been proven clean. That is
the same refusal the README and `CLAUDE.md` already make, put where it survives
being resold, forked, or quoted back during a dispute. For a tool bought to
discharge a legal obligation, the disclaimer is a product feature.

**Not done.** No lawyer has read it. One should, before the first sale, and the
conformance disclaimer is the clause most likely to be tested.

---

## 2026-08-12: Public, and sold anyway

**Decision.** This repository goes public and the addon is listed on the
Marketplace. That reverses "Private first" below, which is left in place rather
than edited, because the reasoning in it was sound and only its premise changed.

**Why the reversal is not a mistake being corrected.** "Private first" rested on
one stated unknown: "the question of whether a listed addon must have a public
repository is itself unanswered". It is answered now, and the answer is yes. A
decision that named the fact that would overturn it, and was overturned by
exactly that fact, worked as intended.

**Why public is right even setting the Marketplace aside.** Every rule this
addon enforces is visible in any browser's element inspector, because the whole
product reads rendered HTML. There is no algorithm here to protect. Keeping the
source closed would defend nothing while costing the two things that actually
sell a compliance tool: an auditor being able to read what it checks, and a buyer
being able to see it is maintained.

**What is actually being sold**, stated plainly so pricing never drifts from it:
the listing, the updates, the support, and a defensible answer when somebody asks
where the accessibility tool came from. Not access to the code. Statamic's
licence check is a control-panel banner and nothing more, verified in source, so
any plan that assumes the code is withheld was never going to work.

**Rejected.** *Stay private and skip the Marketplace*, which forfeits both dogfood
sites' upgrade path along with the revenue: an addon installed from a private
Composer path repository is one nobody else can be handed. *Split the addon
public and the checker private behind a paid registry*, which is the most work,
makes the licence question harder rather than easier, and protects a set of rules
that can be read off the rendered page regardless.

**Not done yet, deliberately.** The repository is still private today. Publishing
it now would publish commercial reasoning and half-formed positioning with no
code to justify either. Nothing needs it public until the package is listed on
packagist.org, so the flip happens when there is something to install.

---

## 2026-08-12: What the Marketplace actually requires, read rather than assumed

Open question 3, answered from Statamic's own pages and from the installed
source at `statamic/cms v6.27.1`. The commercial half first, because one finding
contradicts a decision already taken in this file.

**The money.** 75% commission on every sale, paid to a connected Stripe account
at the time of purchase. The creator must live in a Stripe-supported country;
Statamic names India, Russia, Ukraine and China as currently excluded. Prices
and editions are set by the seller and changeable at any time. No review or
approval process is documented anywhere, which means listing is self-serve and
the quality bar is whatever the seller enforces.

**Distribution, and this is the finding that matters.** Selling requires the
package to be published on packagist.org, which is stated twice: "Publish your
Composer package on packagist.org" and "Publish your Starter Kits and Addons by
linking to GitHub (and Packagist for addons)." Packagist serves public
repositories. **A Marketplace addon therefore cannot have a private source
repository**, which directly contradicts this file's earlier private-first
decision. That decision now needs revisiting rather than quietly surviving.

**Licence enforcement, which is weaker than the word "licence" suggests.**
Statamic's own documentation says: "You can try out commercial addons locally for
free. Be sure to purchase a license before deploying to production." Checked
against the source rather than taken on trust: `LicenseManager` validity reaches
the control panel through `HandleAuthenticatedInertiaRequests::licensing()` as an
`alert` key, and nothing in `src/` aborts, throws, or gates a feature on it. An
unlicensed addon runs. The buyer sees a banner.

So the code is public, the install is unimpeded, and the licence is a banner.
**What is being sold is not access to the code.** It is the listing, the updates,
and the fact that a team with a compliance obligation would rather pay than
explain to their auditor why they vendored an unlicensed accessibility tool. Any
pricing or positioning that assumes the code is withheld is wrong.

The upside of the same fact: `"editions": ["free", "pro"]` in composer.json plus
`Addon::get(...)->edition()` gives a free tier for nothing, and Statamic says
"You don't need to check whether a license is valid, Statamic does that
automatically for you."

---

## 2026-08-12: The gate can refuse a save, verified by running it

The second thing the product depends on, after rendering. Not read: run, against
the same site.

`Statamic\Entries\Entry::save()` dispatches `EntrySaving` and returns early when
a listener returns `false` (`src/Entries/Entry.php:415`). Confirmed live: `save()`
returned `false` and the title on disk was unchanged.

Listeners in an addon's `src/Listeners` directory auto-register from the type hint
alone, so the whole gate is one class.

**The gap, named now rather than discovered in support.** The control panel's
save endpoint returns `'saved' => $saved`, a bare boolean with no message
(`EntriesController`). A refusal with no reason attached is exactly the failure
this product exists to prevent, and it would read as the editor being broken.
The compiled control-panel JavaScript is not shipped in the vendor package, so
what an author actually sees on a `false` save is **unverified**. Finding that
out, and deciding between returning false and throwing a validation exception
that carries the violations, is the first real implementation question.

**Also worth knowing:** there is no `EntryPublishing` event. The list is
Creating, Saving, Saved, Created, Deleting, Deleted, ScheduleReached. So the gate
hangs off saving and has to decide for itself what counts as publishing, which
is where a warn mode and a "only when published" setting will actually live.

---

## 2026-08-12: The control panel UI is a documented component library

`https://ui.statamic.dev` is a Storybook of 54 components, and the ones this
addon needs already exist: `Alert`, `Badge`, `StatusIndicator`, `Panel`,
`Listing`, `EmptyState`, `ConfirmationModal`, `Tooltip`.

This removes a whole category of work and a whole category of risk. A
hand-rolled violations panel would be a bespoke widget inside an accessibility
product, which is the worst possible place to invent one. Use the kit, and any
accessibility defect in the panel is Statamic's to fix rather than ours to have
shipped.

The Storybook is JavaScript-rendered, so its pages cannot be read by fetching
HTML. Read `index.json` for the component list, or read the installed package.

---

## 2026-08-12: An unsaved entry can be rendered, and here is exactly how

**Answered by running it** against a real Statamic 6 / Laravel 13 site
(hada.farm), not by reading documentation. This was the spike that gated the
whole design.

**The recipe, three lines:**

```php
$entry->setSupplement($field, $unsavedValue);  // not set(): set() is not what Live Preview uses
$entry->repository()->substitute($entry);      // so lookups during render return THIS instance
$html = $entry->toResponse($request)->getContent();
```

Verified: the unsaved value appears in the rendered HTML, 27,960 bytes came back
through the site's own templates, and the entry on disk was untouched.

**Why each line is needed, learned by watching it fail.**

`set()` mutates the entry and reaches augmentation correctly, and the rendered
page still showed the SAVED value. `toResponse` resolves the entry again during
rendering rather than trusting the instance it was called on.

`setSupplement` is what Statamic's own `PreviewController::edit` uses to apply
unsaved form values, so it is the supported shape rather than a trick.

`substitute()` is the missing piece, and it is lifted from
`Statamic\Tokens\Handlers\LivePreview`, which does exactly this when a Live
Preview token arrives. Registering the instance with its repository is what makes
the re-resolution during rendering return the modified entry.

**No token, no cache, no CP request needed.** Live Preview wraps this in a token
so a browser can request the front end, but the addon runs inside the same
process as the save, so it can substitute directly and skip all of that.

**A trap worth writing down, because it cost most of the spike.** The first three
attempts reported failure against a page whose template is a hardcoded landing
page that never outputs `{{ title }}`. The mechanism was working by attempt two
and the fixture could not show it. Any test of this must use an entry whose
template actually renders the field being changed, and should assert the
mechanism against a field it has confirmed appears in the output.

**What this does not yet prove:** that rendering is safe to do inside a save
lifecycle hook, that it is fast enough to sit in front of a publish, and what
happens when a template throws for a half-complete entry. Those are the next
questions, and none of them are blocking in the way this one was.

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

> **Superseded the same day by "Public, and sold anyway".** The open question this
> entry named as unresolved got resolved, and it resolved against this decision.
> Kept rather than deleted: a log that only shows decisions which survived teaches
> nothing about how they were made.

**Decision.** The repository starts private and can be opened later.

**Why.** The direction is one-way in practice. A public history stays cached and
forked after a repository is flipped back, and the early commits of this project
will contain half-formed decisions about a product being sold. Nothing about the
Marketplace is blocked by starting private, and the question of whether a listed
addon must have a public repository is itself unanswered (see open questions).

**Rejected.** *Public from the first commit*, which is the better default for a
library nobody pays for and the wrong one for a commercial addon whose core is
extracted from a private codebase.
