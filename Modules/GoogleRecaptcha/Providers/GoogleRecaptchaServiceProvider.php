<?php

namespace Modules\GoogleRecaptcha\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

class GoogleRecaptchaServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerPublic();

        // add recaptcha config to configs table
        $data = [
            'googleInvisibleClient' => '',
            'googleInvisibleServer' => '',
        ];
        addCustomConfig('recaptcha', $data);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Register public resources.
     *
     * @return void
     */
    protected function registerPublic()
    {
        $publicPath = public_path('modules/googlerecaptcha');

        $sourcePath = __DIR__.'/../Public';

        $this->publishes([
            $sourcePath => $publicPath
        ], 'module-googlerecaptcha-public');
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        mergeEvent(
            __DIR__.'/../Config/event.php', 'event'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/googlerecaptcha');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'module-googlerecaptcha-view');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/googlerecaptcha';
        }, \Config::get('view.paths')), [$sourcePath]), 'googlerecaptcha');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/googlerecaptcha');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'googlerecaptcha');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'googlerecaptcha');
        }
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
