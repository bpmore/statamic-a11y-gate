<?php

declare(strict_types=1);

namespace Bpmore\A11yGate;

use Bpmore\A11yGate\Accessibility\StaticAccessibilityChecker;
use Bpmore\A11yGate\Gate\EntryRenderer;
use Bpmore\A11yGate\Gate\GateSettings;
use Bpmore\A11yGate\Gate\PublishGate;
use Bpmore\A11yGate\Scan\SiteScanner;
use Statamic\Contracts\Addons\SettingsRepository;
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

    protected $commands = [
        Commands\CheckSite::class,
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
        //
        // A singleton, which under PHP-FPM is per-request and always fresh. A
        // long-lived worker (Octane, a queue) resolves it once and then holds
        // it, so a save on the settings screen is not seen there until the
        // worker restarts. Accepted for now rather than hidden: nothing in this
        // addon runs on a queue, and an Octane site that flips the mode should
        // reload its workers the same as for any config change.
        $this->app->singleton(GateSettings::class, function () {
            $config = (array) config('statamic-a11y-gate', []);

            // The control-panel screen wins once somebody has saved it, and the
            // config file answers until then. A screen that silently lost to a
            // file would be worse than no screen: somebody changes a setting,
            // saves, sees no change, and stops trusting everything else.
            //
            // **Whether it has been saved is the question, not whether it has
            // values.** An unsaved settings record is not empty: it comes back
            // carrying the blueprint's own defaults, so reading the values alone
            // would let a field default silently beat a config file that a
            // developer wrote on purpose. Found by a test that set the mode in
            // config and watched the gate ignore it.
            // `raw()`, not `all()`. `all()` blends the blueprint's own defaults
            // over what was saved, so a toggle somebody switched off comes back
            // as an empty string and a mode nobody chose comes back as "refuse".
            // `raw()` is exactly what the person saved and nothing else.
            $saved = app(SettingsRepository::class)->find('bpmore/statamic-a11y-gate')?->raw();

            return GateSettings::fromConfig(
                $saved === null ? $config : array_merge($config, array_filter(
                    $saved,
                    // Nulls only. `false` and `[]` are real answers, and treating
                    // them as absent would make "check every collection" and "do
                    // not add the panel" impossible to choose.
                    fn ($value) => $value !== null,
                )),
            );
        });

        $this->app->bind(SiteScanner::class, fn ($app) => new SiteScanner($app->make(PublishGate::class)));

        $this->app->bind(PublishGate::class, fn ($app) => new PublishGate(
            new EntryRenderer($app),
            new StaticAccessibilityChecker,
            $app->make(GateSettings::class),
        ));
    }
}
