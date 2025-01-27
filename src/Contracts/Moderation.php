<?php

namespace Barton\Moderation\Contracts;

interface Moderation
{
    /**
     * Get the current connection name for the model.
     *
     * @return string|null
     */
    public function getConnectionName();

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Get the moderatable model to which this Moderate belongs.
     *
     * @return mixed
     */
    public function moderatable();

    /**
     * User responsible for the changes.
     *
     * @return mixed
     */
    public function user();

    /**
     * Moderate data resolver.
     *
     * @return array
     */
    public function resolveData(): array;

    /**
     * Get an Moderate data value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getDataValue(string $key);

    /**
     * Get the Moderate metadata.
     *
     * @param bool $json
     * @param int  $options
     * @param int  $depth
     *
     * @return array|string
     */
    public function getMetadata(bool $json = false, int $options = 0, int $depth = 512);

    /**
     * Get the Moderatable modified attributes.
     *
     * @param bool $json
     * @param int  $options
     * @param int  $depth
     *
     * @return array|string
     */
    public function getModified(bool $json = false, int $options = 0, int $depth = 512);
}
