<?php

namespace HasanAlyazidi\Sendables\Providers;

use HasanAlyazidi\Sendables\SendablesFacade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class SendablesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sendables.php', 'sendables');

        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'sendables');

        $this->publishAll();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register facade
        $this->app->singleton('sendables', function () {
            return new SendablesFacade;
        });
    }

    /**
     * Publish
     *
     * @return void
     */
    public function publishAll()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/sendables.php' => config_path('sendables.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../../database/migrations/' => database_path('migrations')
            ], 'migrations');

            $this->publishTranslations();
        }
    }

    private function publishTranslations()
    {
        $isLangFolderInResources = File::isDirectory(resource_path('lang'));

        $langBasePath = $isLangFolderInResources
            ? resource_path()
            : base_path();

        $this->publishes([
            __DIR__.'/../../lang/' => $langBasePath.'/lang/vendor/sendables'
        ], 'resources-lang-all');

        $this->publishes([
            __DIR__.'/../../lang/ar/' => $langBasePath.'/lang/vendor/sendables/ar'
        ], 'resources-lang-ar');

        $this->publishes([
            __DIR__.'/../../lang/en/' => $langBasePath.'/lang/vendor/sendables/en'
        ], 'resources-lang-en');
    }
}
