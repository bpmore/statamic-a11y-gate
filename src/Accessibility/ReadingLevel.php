<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * The line above which a plain-language summary is not plain.
 *
 * The grade itself is the host's to compute and stamp, as
 * `data-a11y-reading-grade` on the summary, because by the time the HTML exists
 * a summary is just more text on the page and nothing marks out which words were
 * meant to be the plain ones. Flesch-Kincaid is the formula the threshold is set
 * against: 0.39 x (words per sentence) + 11.8 x (syllables per word) - 15.59.
 * Two ideas, both crude and both real, and worth naming here so a site stamping
 * a number from some other formula knows it is answering a different question.
 *
 * **This class used to carry an implementation of that formula, and it is gone.**
 * Nothing in the addon ever called it, in any commit, and no README line, config
 * comment or decision entry offered it to a host, so it was neither used nor
 * declared. It was also not fit to be declared: its syllable count stripped
 * every character outside `a-z`, so a Japanese, Arabic or Russian passage scored
 * one syllable per word and the grade collapsed into a function of sentence
 * length. Real passages in those scripts came back as 0.0 and 0.1, which is a
 * confident-looking pass for text the formula has no opinion about. Shipping
 * that under this product's name was the expensive mistake, and keeping it
 * unused and undeclared was the ambiguous one.
 *
 * What this is NOT: a decision about WCAG 3.1.5. That criterion is AAA and asks
 * for text at a lower-secondary reading level, judged on meaning. A formula that
 * counts vowel groups cannot establish it. It scores "The cat sat on the mat"
 * and "The mat sat on the cat" identically, and it has no opinion at all about
 * jargon made of short words ("the trust will action the referral").
 *
 * So the check it backs is a WARNING labelled "Reading level (guide)", never an
 * error and never citing a criterion.
 *
 * The number is still worth showing. An author who writes a 22nd-grade
 * "plain-language" summary has not written one, and no reviewer was going to
 * catch that by eye.
 */
final class ReadingLevel
{
    /** Above this, the summary is not plain. Grade 9 = a 14-year-old reader. */
    public const PLAIN_MAX_GRADE = 9;
}
