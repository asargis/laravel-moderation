<?php
namespace Barton\Moderation;

use Barton\Moderation\Contracts\Moderator;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Barton\Moderation\Console\InstallCommand;
use Barton\Moderation\Console\ModerationDriverCommand;


class ModerationServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
        $this->mergeConfigFrom(__DIR__.'/../config/moderation.php', 'moderation');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            ModerationDriverCommand::class,
            InstallCommand::class,
        ]);

        $this->app->singleton(Moderator::class, function ($app) {
            return new \Barton\Moderation\Moderator($app);
        });
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            // Lumen lacks a config_path() helper, so we use base_path()
            $this->publishes([
                __DIR__.'/../config/moderation.php' => base_path('config/moderation.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../database/migrations/moderations.stub' => database_path(
                    sprintf('migrations/%s_create_moderations_table.php', date('Y_m_d_His'))
                ),
                __DIR__.'/../database/migrations/moderation_fields.stub' => database_path(
                    sprintf('migrations/%s_create_moderation_fields_table.php', date('Y_m_d_His'))
                ),
            ], 'migrations');
        }
    }

    /*
    * Get the services provided by the provider.
    *
    * @return array
    */
    public function provides()
    {
        return [
            Moderator::class,
        ];
    }
}
