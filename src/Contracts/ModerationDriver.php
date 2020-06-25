<?php

namespace Barton\Moderation\Contracts;

use Barton\Moderation\Contracts\Moderation;

interface ModerationDriver
{
    /**
     * Perform an moderate.
     *
     * @param \Barton\Moderation\Contracts\Moderatable $model
     *
     * @return \Barton\Moderation\Contracts\Moderation
     */
    public function moderation(Moderatable $model): ?Moderation;

    /**
     * Remove older moderates that go over the threshold.
     *
     * @param \Barton\Moderation\Contracts\Moderatable $model
     *
     * @return bool
     */
    public function prune(Moderatable $model): bool;
}
