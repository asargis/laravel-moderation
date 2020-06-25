<?php

namespace Barton\Moderation\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Moderatable
{
    /**
     * Moderatable Model moderates.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function moderations(): MorphMany;


    /**
     * Get the Moderate Driver.
     *
     * @return string|null
     */
    public function getModerationDriver();

    /**
     * Set the Moderation event.
     *
     * @param string $event
     *
     * @return Moderatable
     */
    public function setModerationEvent(string $event): Moderatable;

    /**
     * Get the Moderation event that is set.
     *
     * @return string|null
     */
    public function getModerationEvent();

    /**
     * Get the events that trigger an Moderation.
     *
     * @return array
     */
    public function getModerationEvents(): array;

    /**
     * Is the model ready for moderating?
     *
     * @return bool
     */
    public function readyForModerating(): bool;

    /**
     * Return data for an Moderation.
     *
     * @throws \Barton\Moderation\Exceptions\ModerationException
     *
     * @return array
     */
    public function toModerate(): array;

    /**
     * Get the (Moderatable) attributes included in audit.
     *
     * @return array
     */
    public function getModerationInclude(): array;

    /**
     * Get the (Moderatable) attributes excluded from audit.
     *
     * @return array
     */
    public function getModerationExclude(): array;

    /**
     * Get the audit (Moderatable) timestamps status.
     *
     * @return bool
     */
    public function getModerationTimestamps(): bool;



}
