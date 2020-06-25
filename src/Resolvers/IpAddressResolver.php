<?php

namespace Barton\Moderation\Resolvers;

use Illuminate\Support\Facades\Request;

class IpAddressResolver implements \Barton\Moderation\Contracts\IpAddressResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve(): string
    {
        return Request::ip();
    }
}
