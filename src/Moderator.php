<?php

namespace Barton\Moderation;

use Barton\Moderation\Events\Moderating;
use Barton\Moderation\Exceptions\ModerationException;
use Barton\Moderation\Models\Moderation;
use Illuminate\Support\Manager;
use Barton\Moderation\Events\Moderated;
use Barton\Moderation\Contracts\Moderatable;
use Barton\Moderation\Contracts\ModerationDriver;
use Barton\Moderation\Drivers\Database;

class Moderator extends Manager implements Contracts\Moderator
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultDriver()
    {
        return 'database';
    }

    /**
     * Create a Database driver instance.
     *
     * @return \barton\Moderation\Drivers\Database
     */
    public function createDriver($driver)
    {
        try {
            return parent::createDriver($driver);
        } catch (\InvalidArgumentException $exception) {
            if (class_exists($driver)) {
                return $this->app->make($driver);
            }

            throw $exception;
        }
    }

    /**
     * Create an instance of the Database audit driver.
     *
     * @return \barton\Moderation\Drivers\Database
     */
    protected function createDatabaseDriver(): Database
    {
        return $this->app->make(Database::class);
    }


    /**
     * {@inheritdoc}
     */
    public function execute(Moderatable $model)
    {
        if (!$model->readyForModerating()) {
            return;
        }

        $driver = $this->moderationDriver($model);

        if (!$this->fireModerationEvent($model, $driver)) {
            return;
        }

        if ($moderation = $driver->moderation($model)) {
            $driver->prune($model);
        }

        $this->app->make('events')->dispatch(
            new Moderated($model, $driver, $moderation)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function moderationDriver(Moderatable $model): ModerationDriver
    {
        $driver = $this->driver($model->getModerationDriver());

        if (!$driver instanceof ModerationDriver) {
            throw new ModerationException('The driver must implement the ModerateDriver contract');
        }

        return $driver;
    }


    /**
     * Fire the Moderation event.
     *
     * @param \Barton\Moderation\Contracts\Moderatable   $model
     * @param \Barton\Moderation\Contracts\ModerationDriver $driver
     *
     * @return bool
     */
    protected function fireModerationEvent(Moderatable $model, ModerationDriver $driver): bool
    {
        return $this->app->make('events')->until(
                new Moderating($model, $driver)
            ) !== false;
    }
}