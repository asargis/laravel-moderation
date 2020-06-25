<?php

namespace Barton\Moderation\Console;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class InstallCommand extends Command
{
    use DetectsApplicationNamespace;

    /**
     * {@inheritdoc}
     */
    protected $signature = 'moderation:install';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Install all of the Moderation resources';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $this->comment('Publishing Moderation Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'config']);

        $this->comment('Publishing Moderation Migrations...');
        $this->callSilent('vendor:publish', ['--tag' => 'migrations']);

        $this->registerModeratingServiceProvider();

        $this->info('Moderation installed successfully.');
    }

    /**
     * Register the Auditing service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerModeratingServiceProvider()
    {
        $namespace = str_replace_last('\\', '', $this->getAppNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, 'Barton\\Moderation\\ModerationServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        Barton\Moderation\ModerationServiceProvider::class,".PHP_EOL,
            $appConfig
        ));
    }
}
