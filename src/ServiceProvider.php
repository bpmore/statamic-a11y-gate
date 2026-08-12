<?php

declare(strict_types=1);

namespace Bpmore\A11yGate;

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Gate\EntryRenderer;
use Bpmore\A11yGate\Gate\GateSettings;
use Bpmore\A11yGate\Gate\PublishGate;
use Statamic\Facades\Utility;
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

    protected $fieldtypes = [
        Fieldtypes\AccessibilityPanel::class,
    ];

    /**
     * A plain script, not a Vite entry point, because Statamic ships the Vue
     * build that compiles templates at runtime. See the panel's own file: this
     * addon needs no npm and no build step.
     */
    protected $scripts = [
        __DIR__.'/../resources/js/a11y-panel.js',
    ];

    protected $viewNamespace = 'a11y-gate';

    /**
     * A page under Tools that says what the addon checks and what it cannot.
     *
     * It exists because that explanation was being printed under every result,
     * on every entry, forever. A sentence repeated that often is a sentence
     * nobody reads, and the person it was aimed at could not act on any of it.
     * Somebody who wants to know what the tool is worth comes looking once.
     */
    public function bootAddon()
    {
        Utility::extend(fn () => Utility::register(
            Utility::make('a11y-gate')
                ->title('Accessibility Gate')
                ->navTitle('Accessibility Gate')
                ->icon('clipboard-check')
                ->description('What this checks before a publish, and what it cannot.')
                ->view('a11y-gate::utilities.gate')
        ));
    }

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
