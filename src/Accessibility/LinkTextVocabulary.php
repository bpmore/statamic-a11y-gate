<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * The word lists behind the link-purpose rules.
 *
 * Windrow reads these from `config()`. Here they are constructor arguments whose
 * defaults are Windrow's current values, byte for byte, so the two forks answer
 * the same on the same page unless a host deliberately says otherwise. That
 * matters more than usual: `link-unclear` and `link-vague` are the only rules in
 * the pack whose verdict depends on a list rather than on the markup, so this
 * file is where the two projects would drift first and most quietly.
 *
 * A host that customises these has left the shared corpus behind on purpose, and
 * the corpus is run against the defaults for that reason.
 */
final class LinkTextVocabulary
{
    /**
     * @param  array<int, string>  $banned  refuses the publish when the whole normalised name equals the phrase
     * @param  array<int, string>  $bannedSubstrings  refuses even with trailing words ("click here to read the report" is still "click here")
     * @param  array<int, string>  $vague  warns when the phrase sits inside longer text that still names no destination
     * @param  array<int, string>  $filler  content-free function words: see `allWordsGeneric` for what they are for
     */
    public function __construct(
        public readonly array $banned = [
            'click here', 'click', 'here', 'read more', 'learn more', 'more',
            'more info', 'details', 'this', 'this page', 'link', 'go',
            'continue', 'continue reading', 'read', 'see more', 'find out more', 'download',
        ],
        public readonly array $bannedSubstrings = ['click here', 'click this'],
        public readonly array $vague = ['read more', 'learn more', 'more', 'details', 'this', 'link'],
        public readonly array $filler = [
            'a', 'an', 'the', 'and', 'or', 'of', 'to', 'for', 'on', 'in', 'at',
            'with', 'from', 'by', 'about', 'our', 'your', 'my', 'their', 'his',
            'her', 'its', 'we', 'you', 'us', 'it', 'that', 'those', 'these',
            'here', 'there', 'now', 'today', 'all', 'out', 'up', 'get', 'can',
            'will', 'just', 'please', 'very', 'what', 'how', 'why', 'when',
            'where', 'who',
        ],
    ) {}

    /**
     * Every word that carries no destination meaning: the filler list plus each
     * word of the banned and vague phrases ("read", "more", "click", ...). The
     * vague tier only warns when a name is built entirely from these, so that
     * "Learn more about us" warns and "Learn more about Soundgarden" does not.
     *
     * @return array<string, true>
     */
    public function genericWords(): array
    {
        $words = $this->filler;

        foreach ([...$this->banned, ...$this->vague, ...$this->bannedSubstrings] as $phrase) {
            foreach (explode(' ', $phrase) as $word) {
                $words[] = $word;
            }
        }

        return array_fill_keys(array_filter($words), true);
    }
}
