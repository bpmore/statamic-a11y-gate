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
   arrangement. Answer before the first line of shared code. **Now sharper:**
   selling on the Marketplace makes this repository public, so the licence is
   not protecting the source. It is deciding what a competitor may legally do
   with rules they can already read.
2. **Where the checker lives.** Windrow's repo is private and an addon must be
   installable. Either the checker becomes its own package, or this repo carries
   its own copy, and the second option is the one the plan explicitly forbids
   because two copies of the rules drift.
3. ~~**Marketplace requirements.**~~ **Read, and the answer changed question 1.**
   Selling requires a package on packagist.org, so the repository must be public.
   Licence enforcement is a banner, not a block. Details in the next entry.
4. ~~**The spike.** Can an addon render an unsaved entry through the site's own
   templates?~~ **Answered on the same day: yes.** The recipe and the three
   attempts it took to find it are the next entry. Struck rather than deleted, so
   the record shows this was the question the design hung on rather than
   suggesting nobody thought to ask.

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
