<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Tests;

use Bpmore\A11yGate\ServiceProvider;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\FakesViews;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

/**
 * A booted Statamic, for the half of this addon that cannot be tested without
 * one.
 *
 * The checker's own tests boot nothing and must stay that way. This is only for
 * the gate: an entry, a template, and a save that has to be refused.
 */
abstract class TestCase extends AddonTestCase
{
    use FakesViews;
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;
}
