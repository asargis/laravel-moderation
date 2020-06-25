<?php

namespace Barton\Moderation\Drivers;

use Barton\Moderation\Contracts\Moderation;
use Illuminate\Support\Facades\Config;
use Barton\Moderation\Contracts\Moderatable;
use Barton\Moderation\Contracts\ModerationDriver;
use Illuminate\Support\Facades\DB;

class Database implements ModerationDriver
{
    /**
     * {@inheritdoc}
     */
    public function moderation(Moderatable $model): ?Moderation
    {
        $moderationImplementation = Config::get('moderation.implementation.moderation', \Barton\Moderation\Models\Moderation::class);
        $moderationFieldsImplementation = Config::get('moderation.implementation.moderation_fields', \Barton\Moderation\Models\ModerationField::class);

        $data = $model->toModerate();
        $moderationData = $data['moderation'];
        $moderationFieldsData = $data['moderation_fields'];

        if (isset($moderationFieldsData) && count($moderationFieldsData) > 0) {
            $moderation = call_user_func([$moderationImplementation, 'create'], $moderationData);
            if(isset($moderation) && !empty($moderation)) {
                foreach ($moderationFieldsData as $key => $fieldsData) {
                    $fieldsData['moderation_id'] = $moderation->id;
                    call_user_func([$moderationFieldsImplementation, 'create'], $fieldsData);
                }

                return $moderation;
            }
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function prune(Moderatable $model): bool
    {
        if (($threshold = $model->getModerationThreshold()) > 0) {
            $forRemoval = $model->moderations()
                ->latest()
                ->get()
                ->slice($threshold)
                ->pluck('id');

            if (!$forRemoval->isEmpty()) {
                return $model->moderations()
                    ->whereIn('id', $forRemoval)
                    ->delete() > 0;
            }
        }

        return false;
    }
}
