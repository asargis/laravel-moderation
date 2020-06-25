<?php

namespace Barton\Moderation\Events;

use Barton\Moderation\Contracts\Moderatable;
use Barton\Moderation\Contracts\ModerationDriver;

class Moderating
{
    /**
     * The Moderatable model.
     *
     * @var \Barton\Moderation\Contracts\Moderatable
     */
    public $model;

    /**
     * Moderation driver.
     *
     * @var \Barton\Moderation\Contracts\ModerationDriver
     */
    public $driver;

    /**
     * Create a new Moderation event instance.
     *
     * @param \Barton\Moderation\Contracts\Moderatable   $model
     * @param \Barton\Moderation\Contracts\ModerationDriver $driver
     */
    public function __construct(Moderatable $model, ModerationDriver $driver)
    {
        $this->model = $model;
        $this->driver = $driver;
    }
}
