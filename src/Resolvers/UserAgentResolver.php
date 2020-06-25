<?php

namespace Barton\Moderation\Resolvers;

use Illuminate\Support\Facades\Request;

class UserAgentResolver implements \Barton\Moderation\Contracts\UserAgentResolver
{
    /**
     * {@inheritdoc}
     */
    public static function resolve()
    {
        return Request::header('User-Agent');
    }
}
