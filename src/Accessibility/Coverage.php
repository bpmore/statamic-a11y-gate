<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Accessibility;

/**
 * How much of what a check judges it could actually see in this document.
 *
 * The reason this class exists, stated plainly because it is the product: **a
 * check that silently finds nothing is indistinguishable from a check that
 * passed.** Three of the seven families read attributes a host has to stamp, and
 * a fourth can only see sizes written into the markup. On an ordinary Statamic
 * site those four are quiet, and without this they would be quiet in exactly the
 * way a clean page is quiet.
 *
 * Three extents, and each one exists to stop a specific lie:
 *
 * - `full`: the check saw everything it judges. A page with no images is a full
 *   pass for the image rule, not a gap: there was nothing there to miss.
 * - `partial`: it ran, and part of what it judges was invisible here. Target
 *   size is the standing example: a size in a stylesheet cannot be read off the
 *   markup.
 * - `none`: it could not run at all, because what it reads is not in this page.
 *
 * There is deliberately no "nothing to judge" extent. It sounds more precise and
 * it is not: for the checks that always work, having nothing to look at IS a
 * complete answer, and giving it its own name would make a clean page look like
 * a hole.
 */
final class Coverage
{
    public const FULL = 'full';

    public const PARTIAL = 'partial';

    public const NONE = 'none';

    private function __construct(
        public readonly string $check,
        public readonly string $name,
        public readonly string $extent,
        /** Empty when the extent is full. Written for an author, not a developer. */
        public readonly string $limit,
    ) {}

    public static function full(string $check, string $name): self
    {
        return new self($check, $name, self::FULL, '');
    }

    public static function partial(string $check, string $name, string $limit): self
    {
        return new self($check, $name, self::PARTIAL, $limit);
    }

    public static function none(string $check, string $name, string $limit): self
    {
        return new self($check, $name, self::NONE, $limit);
    }

    public function ran(): bool
    {
        return $this->extent !== self::NONE;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'check' => $this->check,
            'name' => $this->name,
            'extent' => $this->extent,
            'limit' => $this->limit,
        ];
    }
}
