<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Contracts;

use Statamic\Contracts\Entries\Entry;

/**
 * Another addon's check, run by this gate as part of one publish.
 *
 * Without this, two addons that each refuse a publish do it one after the
 * other: the author fixes one list, presses Publish, and only then meets
 * the second. With it, the gate asks every contributor during the same
 * save and refuses once, with every list, each under its own key so each
 * addon's panel draws its own part.
 *
 * Sign up by tagging the class, by name, so the contributing addon never
 * has to require this one:
 *
 *     if (interface_exists(\Bpmore\A11yGate\Contracts\GateContributor::class)) {
 *         $this->app->tag(MyContributor::class, 'a11y-gate.contributors');
 *     }
 *
 * A contributor decides its own rules: which collections it gates, whether
 * a draft counts, whether its own broken check blocks, and whether someone
 * may publish past it. This gate does not second-guess the answer. A
 * contributor that throws is logged and left out: a broken addon is never
 * the reason a site cannot publish. The gate's own `warn` mode is about
 * its own checks only; a contributor that blocks still blocks.
 */
interface GateContributor
{
    public const TAG = 'a11y-gate.contributors';

    /**
     * A short, stable slug: the key its lines are refused under, which is
     * the key its panel reads (`copydesk`). Never `a11y_gate`.
     */
    public function key(): string;

    /** The addon's name, as an author knows it: "Copydesk". */
    public function label(): string;

    public function inspect(Entry $entry): ContributedResult;
}
