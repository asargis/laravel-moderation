<?php

return [

    'enabled' => env('MODERATING_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Moderate Implementation
    |--------------------------------------------------------------------------
    |
    | Define which Moderate model implementation should be used.
    |
    */

    'implementation' => [
        'moderation' => Barton\Moderation\Models\Moderation::class,
        'moderation_fields' => Barton\Moderation\Models\ModerationField::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | User Morph prefix & Guards
    |--------------------------------------------------------------------------
    |
    | Define the morph prefix and authentication guards for the User resolver.
    |
    */

    'user' => [
        'morph_prefix' => 'user',
        'guards'       => [
            'web',
            'api',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderate Resolvers
    |--------------------------------------------------------------------------
    |
    | Define the User, IP Address, User Agent and URL resolver implementations.
    |
    */
    'resolver' => [
        'user'       => Barton\Moderation\Resolvers\UserResolver::class,
        'ip_address' => asargis\Moderating\Resolvers\IpAddressResolver::class,
        'user_agent' => asargis\Moderating\Resolvers\UserAgentResolver::class,
        'url'        => asargis\Moderating\Resolvers\UrlResolver::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderate Events
    |--------------------------------------------------------------------------
    |
    | The Eloquent events that trigger an Moderation.
    |
    */

    'events' => [
        'created',
        'updated',
//        'deleted',
//        'restored',
    ],

    /*
    |--------------------------------------------------------------------------
    | Strict Mode
    |--------------------------------------------------------------------------
    |
    | Enable the strict mode when moderating?
    |
    */

    'strict' => false,

    /*
    |--------------------------------------------------------------------------
    | Moderation Timestamps
    |--------------------------------------------------------------------------
    |
    | Should the created_at, updated_at and deleted_at timestamps be moderated?
    |
    */

    'timestamps' => false,

    /*
    |--------------------------------------------------------------------------
    | Moderation Threshold
    |--------------------------------------------------------------------------
    |
    | Specify a threshold for the amount of Moderation records a model can have.
    | Zero means no limit.
    |
    */

    'threshold' => 0,

    /*
    |--------------------------------------------------------------------------
    | Moderation Driver
    |--------------------------------------------------------------------------
    |
    | The default moderation driver used to keep track of changes.
    |
    */

    'driver' => 'database',

    /*
    |--------------------------------------------------------------------------
    | Moderation Driver Configurations
    |--------------------------------------------------------------------------
    |
    | Available moderation drivers and respective configurations.
    |
    */

    'drivers' => [
        'database' => [
            'table'      => 'moderations',
            'connection' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Moderate Console
    |--------------------------------------------------------------------------
    |
    | Whether console events should be audited (eg. php artisan db:seed).
    |
    */

    'console' => false,
];
