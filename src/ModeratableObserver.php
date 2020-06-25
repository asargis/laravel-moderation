<?php

namespace Barton\Moderation;

use Barton\Moderation\Contracts\Moderatable;
use Barton\Moderation\Facades\Moderator;

class ModeratableObserver
{
    /**
     * Is the model being restored?
     *
     * @var bool
     */
    public static $restoring = false;

    /**
     * Handle the retrieved event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function retrieved(Moderatable $model)
    {
        Moderator::execute($model->setModerationEvent('retrieved'));
    }

    /**
     * Handle the created event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function created(Moderatable $model)
    {
        Moderator::execute($model->setModerationEvent('created'));
    }

    /**
     * Handle the updated event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function updated(Moderatable $model)
    {
        // Ignore the updated event when restoring
        if (!static::$restoring) {
            Moderator::execute($model->setModerationEvent('updated'));
        }
    }

    /**
     * Handle the deleted event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function deleted(Moderatable $model)
    {
        Moderator::execute($model->setModerationEvent('deleted'));
    }

    /**
     * Handle the restoring event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function restoring(Moderatable $model)
    {
        // When restoring a model, an updated event is also fired.
        // By keeping track of the main event that took place,
        // we avoid creating a second audit with wrong values
        static::$restoring = true;
    }

    /**
     * Handle the restored event.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function restored(Moderatable $model)
    {
        Moderator::execute($model->setModerationEvent('restored'));

        // Once the model is restored, we need to put everything back
        // as before, in case a legitimate update event is fired
        static::$restoring = false;
    }
}
