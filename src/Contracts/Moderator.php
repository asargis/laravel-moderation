<?php

namespace Barton\Moderation\Contracts;

interface Moderator
{
    /**
     * Get an moderate driver instance.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return ModerationDriver
     */
    public function moderationDriver(Moderatable $model): ModerationDriver;

    /**
     * Perform an moderate.
     *
     * @param \barton\Moderation\Contracts\Moderatable $model
     *
     * @return void
     */
    public function execute(Moderatable $model);
}
