<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * An estimate of how hard a passage is to read, as a US school grade.
 *
 * Flesch-Kincaid: 0.39 x (words per sentence) + 11.8 x (syllables per word) -
 * 15.59. Two ideas, both crude and both real: long sentences are harder than
 * short ones, and long words are harder than short ones.
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

    /**
     * The Flesch-Kincaid grade for a passage, or null when there is too little
     * text to say anything honest about.
     *
     * One sentence is not a sample. The formula divides by sentence count and
     * word count, so a three-word fragment produces a confident-looking number
     * built on nothing, which is worse than no number, because somebody will act
     * on it.
     */
    public static function grade(string $text): ?float
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $sentences = self::countSentences($text);
        $words = self::words($text);

        if ($sentences < 2 || count($words) < 20) {
            return null;
        }

        $syllables = 0;
        foreach ($words as $word) {
            $syllables += self::syllables($word);
        }

        $grade = 0.39 * (count($words) / $sentences)
            + 11.8 * ($syllables / count($words))
            - 15.59;

        // Negative grades are an artifact of very short words in very short
        // sentences ("I go. You go. We go."), not a meaningful reading level.
        return round(max($grade, 0), 1);
    }

    /**
     * Sentences, counted by terminal punctuation.
     *
     * A passage with no full stop at all is one sentence, not zero: otherwise a
     * heading-shaped line divides by zero. Trailing punctuation runs ("...",
     * "?!") count once.
     */
    private static function countSentences(string $text): int
    {
        $count = preg_match_all('/[.!?]+(?=\s|$)/u', $text);

        return max((int) $count, 1);
    }

    /**
     * @return array<int, string>
     */
    private static function words(string $text): array
    {
        preg_match_all("/[\p{L}']+/u", $text, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Syllables in one word, by vowel groups.
     *
     * The standard heuristic and the standard caveats: a trailing silent "e" is
     * dropped, and every word counts as at least one. It over-counts "queue" and
     * under-counts "poem". Averaged over twenty words or more, which is the floor
     * this class enforces, the errors mostly cancel.
     */
    private static function syllables(string $word): int
    {
        $word = mb_strtolower($word, 'UTF-8');
        $word = preg_replace('/[^a-z]/', '', $word) ?? '';

        if ($word === '') {
            return 1;
        }

        // "make" is one syllable, "the" is one, but "be" and "he" are too, so
        // only drop the silent e when something is left behind it.
        if (str_ends_with($word, 'e') && strlen($word) > 2) {
            $word = substr($word, 0, -1);
        }

        $groups = preg_match_all('/[aeiouy]+/', $word);

        return max((int) $groups, 1);
    }
}
