<?php

namespace Barton\Moderation\Console;

use Illuminate\Console\GeneratorCommand;

class ModerationDriverCommand extends GeneratorCommand
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'moderation:moderation-driver';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Create a new moderation driver';

    /**
     * {@inheritdoc}
     */
    protected $type = 'ModerationDriver';

    /**
     * {@inheritdoc}
     */
    protected function getStub()
    {
        return __DIR__.'/../../drivers/driver.stub';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\ModerationDrivers';
    }
}
