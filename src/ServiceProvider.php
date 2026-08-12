<?php

declare(strict_types=1);

namespace Bpmore\A11yGate;

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Gate\EntryRenderer;
use Bpmore\A11yGate\Gate\GateSettings;
use Bpmore\A11yGate\Gate\PublishGate;
use Statamic\Providers\AddonServiceProvider;

/**
 * The whole addon: a config file, three bindings and one listener.
 *
 * The listener is not registered here. Statamic discovers anything in
 * `src/Listeners` and binds it to the event named by its first type hint, so the
 * gate is wired by the argument to `handle()` and nothing else.
 */
class ServiceProvider extends AddonServiceProvider
{
    protected $config = true;

    public function register()
    {
        parent::register();

        // Resolved lazily, because the addon's config is merged during boot and
        // this runs before that.
        $this->app->singleton(GateSettings::class, fn () => GateSettings::fromConfig(
            (array) config('a11y-gate', []),
        ));

        $this->app->bind(PublishGate::class, fn ($app) => new PublishGate(
            new EntryRenderer($app),
            new StaticAccessibilityChecker,
            $app->make(GateSettings::class),
        ));
    }
}
