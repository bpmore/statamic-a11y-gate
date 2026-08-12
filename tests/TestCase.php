<?php

declare(strict_types=1);

namespace Bpmore\A11yGate\Tests;

use Bpmore\A11yGate\ServiceProvider;
use Statamic\Facades\Blueprint;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\FakesRoles;
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
    use FakesRoles;
    use FakesViews;
    use PreventsSavingStacheItemsToDisk;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        // Testbench ships no app key, and the control-panel routes go through
        // the session middleware, which encrypts.
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        // Two users is a Pro feature, and one of these tests needs a second user
        // to prove the permission check refuses somebody who cannot view the entry.
        $app['config']->set('statamic.editions.pro', true);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // A real blueprint, because the panel's endpoint processes submitted
        // values through one and a blueprint with no fields silently processes
        // them to nothing. Without this the endpoint's tests pass by checking an
        // unchanged page, which is the failure they exist to catch.
        Blueprint::setDirectory(__DIR__.'/__fixtures__/blueprints');
    }
}
