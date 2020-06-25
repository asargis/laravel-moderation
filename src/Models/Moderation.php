<?php

namespace Barton\Moderation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class Moderation extends Model implements \Barton\Moderation\Contracts\Moderation
{
    use \Barton\Moderation\Moderation;

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    public function fields()
    {
        return $this->hasMany(Config::get('moderation.implementation.moderation_fields', Models\ModerationFields::class));
    }
}