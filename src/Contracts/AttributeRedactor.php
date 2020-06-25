<?php

namespace Barton\Moderation\Contracts;

interface AttributeRedactor extends AttributeModifier
{
    /**
     * Redact an attribute value.
     *
     * @param mixed $value
     *
     * @return string
     */
    public static function redact($value): string;
}
