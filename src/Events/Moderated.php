<?php

namespace Barton\Moderation\Events;

use Barton\Moderation\Contracts\Moderation;
use Barton\Moderation\Contracts\Moderatable;
use Barton\Moderation\Contracts\ModerationDriver;

class Moderated
{
    /**
     * The Moderatable model.
     *
     * @var \barton\Moderation\Contracts\Moderatable
     */
    public $model;

    /**
     * Moderate driver.
     *
     * @var \barton\Moderation\Contracts\ModerationDriver
     */
    public $driver;

    /**
     * The Moderate model.
     *
     * @var \barton\Moderation\Contracts\Moderation|null
     */
    public $moderate;

    /**
     * Create a new Moderated event instance.
     *
     * @param \barton\Moderation\Contracts\Moderatable      $model
     * @param \barton\Moderation\Contracts\ModerationDriver $driver
     * @param \barton\Moderation\Contracts\Moderation       $audit
     */
    public function __construct(Moderatable $model, ModerationDriver $driver, Moderation $moderate = null)
    {
        $this->model = $model;
        $this->driver = $driver;
        $this->moderate = $moderate;
    }
}
