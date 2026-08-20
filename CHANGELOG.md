# Changelog

What changed in each release, and what you have to do about it.

Anything that can stop a site being checked leads its section, because a check
that quietly stops running looks exactly like a page with nothing wrong. That is
the one failure this addon exists to refuse, and a release note that buries it
is the same failure in another file.

Versions are `MAJOR.MINOR.PATCH`. Before 1.0 a breaking change raises the minor,
so pin `^0.6` rather than `^0` if that matters to you.

## 0.6.0 (2026-08-19)

### Removed, and it can break a site

**`ReadingLevel::grade()` is gone**, along with the three private helpers behind
it.

It is very unlikely you were calling it. It was never called by the addon
itself, in any commit, and it was named in no README line, no config comment and
no decision entry, so the only way to find it was to read `src/`. There is no
Antlers tag or modifier for it, so no template could have reached it.

**If you were calling it**, from a listener or a service that stamps
`data-a11y-reading-grade` on your plain-language summaries, that call is now a
fatal error. Compute the grade yourself instead. The formula the threshold is
calibrated against is Flesch-Kincaid:

    0.39 x (words per sentence) + 11.8 x (syllables per word) - 15.59

`ReadingLevel::PLAIN_MAX_GRADE` stays, and is still the number the check
compares your stamped grade against.

Why it was removed rather than documented: its syllable count stripped every
character outside `a-z`, so a word in Japanese, Arabic or Russian counted as one
syllable and the grade collapsed into a function of sentence length alone. Real
passages in those scripts scored 0.0 and 0.1 against a threshold of 9, which is
a confident-looking pass for text the formula has no opinion about. Publishing
that as a supported API was not something this addon could do honestly, and
leaving it in place unused and undeclared was the ambiguity that got it noticed.

There is deliberately no deprecation shim. A method that still returns 0.1 for
Arabic is worse kept than removed.

### Fixed, where the addon was saying something untrue

- A page whose only media was a `figure` was told to confirm that its video had
  captions. It has no video. A page that did carry the markup for captions,
  transcripts, figure text and footnotes was told that captions "are only
  checked on sites that mark them up, and this one does not", in the same report
  as a finding only that markup could have produced.
- The Accessibility Gate page under Tools said there were three exceptions that
  do not stop a publish. There are four: link text that is vague rather than
  plainly wrong was missing. The same page said the entry "is not saved" without
  mentioning that report mode is a setting on the screen it sends you to, gave
  24 pixels as the touch target minimum without saying a config file raises it
  to 44, and said "embedded videos" where the rule checks every frame, maps and
  forms included.
- In report mode the log said "This entry was not saved" about an entry that was
  saved.
- The site-wide scan counted a page once per finding rather than once. A page
  with three identical "Read more" links reported as three pages, stopped naming
  the page it was on, and on a small site could claim "every page", which is the
  line that sends somebody to rewrite a template.

### Changed, with no difference to what is reported

- Severity now lives with the rule rather than being passed at each of the
  eighteen places a rule is raised. A rule can no longer be raised at two
  different severities, and a typo can no longer turn a refusal into a warning.
- The opt-in setting is read once, by the checker. Every check now always
  answers how much of the page it could see, so a check can no longer drop out
  of the count and make "4 of 5 checks ran" read as "4 of 4".
- `AccessibilityStandard` is a backed enum. It used to be three fields anyone
  could set, so a label claiming AAA could sit over a minimum of 7 pixels.
- The corpus refuses to run against a fixture it cannot read, and pins its cases
  by name rather than by counting them.

Verified across all 28 corpus fixtures, before and after, including every
message, call to action and pointer: no finding changed.

## Earlier releases

Not written up. This file starts here, and the reasoning behind the choices
above, including the alternatives turned down, is in `docs/DECISIONS.md`.
