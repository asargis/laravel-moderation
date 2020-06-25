<?php

namespace Barton\Moderation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Barton\Moderation\Contracts\ModerationDriver moderationDriver(\Barton\Moderation\Contracts\Moderatable $model);
 * @method static void execute(\Barton\Moderation\Contracts\Moderatable $model);
 */
class Moderator extends Facade
{
    /**
     * {@inheritdoc}
     */
    protected static function getFacadeAccessor()
    {
        return \Barton\Moderation\Contracts\Moderator::class;
    }
}
