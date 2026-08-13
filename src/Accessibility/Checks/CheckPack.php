<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility\Checks;

/**
 * Every rule family, in the order they run.
 *
 * A hard-coded list, not a directory scan. The answer to "what does the gate
 * actually check?" should be something you can read in ten seconds rather than a
 * convention you have to trust.
 *
 * Order is part of the contract. A refusal renders its issues in the order it
 * receives them, so a pack that runs the same checks in a different sequence
 * shows an author something different first. The corpus pins it.
 */
final class CheckPack
{
    /**
     * The complete pack. Adding a family is a code change, a corpus case, and a
     * test, in that order of importance.
     *
     * @var array<int, class-string<Check>>
     */
    public const CHECKS = [
        HeadingOrderCheck::class,
        LinkPurposeCheck::class,
        ImageAltCheck::class,
        MediaAlternativesCheck::class,
        TargetSizeCheck::class,
        InternalLinkCheck::class,
        ReadingLevelCheck::class,
    ];

    /** @return array<int, Check> */
    public static function all(): array
    {
        return array_map(fn (string $class) => new $class, self::CHECKS);
    }

    /**
     * Every rule the pack can raise.
     *
     * The point of `Check::rules()`: a test can assert the pack still covers
     * every rule without running any of them, and the corpus test uses it to
     * fail when a rule has no fixture. A rule with no fixture is a rule the two
     * forks cannot be held to.
     *
     * @return array<int, string>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::CHECKS as $class) {
            foreach ($class::rules() as $rule) {
                $rules[] = $rule;
            }
        }

        sort($rules);

        return array_values(array_unique($rules));
    }
}
