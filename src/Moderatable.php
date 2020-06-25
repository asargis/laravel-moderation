<?php

namespace Barton\Moderation;

use Barton\Moderation\Contracts\AttributeRedactor;
use Barton\Moderation\Contracts\IpAddressResolver;
use Barton\Moderation\Contracts\UrlResolver;
use Barton\Moderation\Contracts\UserAgentResolver;
use Barton\Moderation\Contracts\UserResolver;
use Barton\Moderation\Exceptions\ModeratableTransitionException;
use Barton\Moderation\Exceptions\ModerationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait Moderatable
{
    /**
     * Moderatable attributes excluded from the Moderation.
     *
     * @var array
     */
    protected $excludedAttributes = [];

    /**
     * Moderation event name.
     *
     * @var string
     */
    protected $moderationEvent;

    /**
     * Is moderating disabled?
     *
     * @var bool
     */
    public static $moderatingDisabled = false;

    /**
     * Moderatable boot logic.
     *
     * @return void
     */
    public static function bootModeratable()
    {
        if (!self::$moderatingDisabled && static::isModeratingEnabled()) {
            static::observe(new ModeratableObserver());
        }
    }

    public function save(array $options = [])
    {
        if (is_null($this->moderation_active))  $this->moderation_active = false;
        $query = $this->newModelQuery();

        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This provides a chance for any
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        // If the model already exists in the database we can just update our record
        // that is already in this database using the current IDs in this "where"
        // clause to only update this model. Otherwise, we'll just insert them.
        if ($this->exists) {
            if(!static::$moderatingDisabled && static::isModeratingEnabled()) {
                $saved = $this->isDirty() ?
                    $this->performUpdate($query) : true;
            } else {
                $saved = $this->isDirty() ?
                    parent::performUpdate($query) : true;
            }
        }

        // If the model is brand new, we'll insert it into our database and set the
        // ID attribute on the model to the value of the newly inserted row's ID
        // which is typically an auto-increment value managed by the database.
        else {
            $saved = $this->performInsert($query);

            if (! $this->getConnectionName() &&
                $connection = $query->getConnection()) {
                $this->setConnection($connection->getName());
            }
        }

        // If the model is successfully saved, we need to do a few more things once
        // that is done. We will call the "saved" method here to run any actions
        // we need to happen after a model gets successfully saved right here.
        if ($saved) {
//            $this->finishSave($options);

            return true;
        }

        return $saved;
    }

    protected function performUpdate(Builder $query)
    {
            $includedFields = $this->getModerationInclude();
            $attributes = $this->getAttributes();
            $attrsForSave = [];

            foreach ($attributes as $attrKey => $attrValue) {
                if (!in_array($attrKey, $includedFields)) {
                    $attrsForSave[$attrKey] = $attrValue;
                }
            }

            DB::table($this->getTable())
                ->where(['id' => $this->id])
                ->update($attrsForSave);

            return $this->fireModelEvent('updated', false);
    }

    public function scopeIsModerationActive($query)
    {
        return $query->where('moderation_active', true);
    }

    public function getOnModerationAttribute(): bool
    {
        return !$this->moderation_active || $this->pending_moderations()->exists();
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->moderation_active;
    }

    /**
     * Determine if an attribute is eligible for auditing.
     *
     * @param string $attribute
     *
     * @return bool
     */
    protected function isAttributeModeratable(string $attribute): bool
    {
        // The attribute should not be audited
        if (in_array($attribute, $this->excludedAttributes, true)) {
            return false;
        }

        // The attribute is moderatable when explicitly
        // listed or when the include array is empty
        $include = $this->getModerationInclude();

        return empty($include) || in_array($attribute, $include, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationDriver()
    {
        return $this->moderationDriver ?? Config::get('moderation.driver', 'database');
    }

    /**
     * {@inheritdoc}
     */
    public function moderations(): MorphMany
    {
        return $this->morphMany(
            Config::get('moderation.implementation.moderation', Models\Moderation::class),
            'entity'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function pending_moderations(): MorphMany
    {
        return $this->morphMany(
            Config::get('moderation.implementation.moderation', Models\Moderation::class),
            'entity'
        )->where(['status' => 'pending']);
    }


    /**
     * {@inheritdoc}
     */
    public function getModerationInclude(): array
    {
        return $this->moderationInclude ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationExclude(): array
    {
        return $this->moderationExclude ?? [];
    }

    /**
     * Determine whether an event is moderatable.
     *
     * @param string $event
     *
     * @return bool
     */
    protected function isEventModeratable($event): bool
    {
        return is_string($this->resolveAttributeGetter($event));
    }

    /**
     * Attribute getter method resolver.
     *
     * @param string $event
     *
     * @return string|null
     */
    protected function resolveAttributeGetter($event)
    {
        foreach ($this->getModerationEvents() as $key => $value) {
            $moderatableEvent = is_int($key) ? $value : $key;

            $moderatableEventRegex = sprintf('/%s/', preg_replace('/\*+/', '.*', $moderatableEvent));

            if (preg_match($moderatableEventRegex, $event)) {
                return is_int($key) ? sprintf('get%sEventAttributes', ucfirst($event)) : $value;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setModerationEvent(string $event): Contracts\Moderatable
    {
        $this->moderationEvent = $this->isEventModeratable($event) ? $event : null;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationEvent()
    {
        return $this->moderationEvent;
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationEvents(): array
    {
        return $this->moderationEvents ?? Config::get('moderation.events', [
                'created',
                'updated',
                'deleted',
                'restored',
            ]);
    }


    /**
     * {@inheritdoc}
     */
    public function readyForModerating(): bool
    {
        if (static::$moderatingDisabled) {
            return false;
        }

        return $this->isEventModeratable($this->moderationEvent);
    }

    /**
     * Disable Moderating.
     *
     * @return void
     */
    public static function disableModerating()
    {
        static::$moderatingDisabled = true;
    }

    /**
     * Enable Moderating.
     *
     * @return void
     */
    public static function enableModerating()
    {
        static::$moderatingDisabled = false;
    }

    /**
     * Determine whether moderating is enabled.
     *
     * @return bool
     */
    public static function isModeratingEnabled(): bool
    {
        if (App::runningInConsole()) {
            return Config::get('moderation.console', false);
        }

        return Config::get('moderation.enabled', true);
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationTimestamps(): bool
    {
        return $this->moderationTimestamps ?? Config::get('moderation.timestamps', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeModifiers(): array
    {
        return $this->attributeModifiers ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function generateTags(): array
    {
        return [];
    }

    /**
     * Modify attribute value.
     *
     * @param string $attribute
     * @param mixed  $value
     *
     * @throws ModerationException
     *
     * @return mixed
     */
    protected function modifyAttributeValue(string $attribute, $value)
    {
        $attributeModifiers = $this->getAttributeModifiers();

        if (!array_key_exists($attribute, $attributeModifiers)) {
            return $value;
        }

        $attributeModifier = $attributeModifiers[$attribute];

        if (is_subclass_of($attributeModifier, AttributeRedactor::class)) {
            return call_user_func([$attributeModifier, 'redact'], $value);
        }

        if (is_subclass_of($attributeModifier, AttributeEncoder::class)) {
            return call_user_func([$attributeModifier, 'encode'], $value);
        }

        throw new ModerationException(sprintf('Invalid AttributeModifier implementation: %s', $attributeModifier));
    }

    /**
     * Resolve the User.
     *
     * @throws ModerationException
     *
     * @return mixed|null
     */
    protected function resolveUser()
    {
        $userResolver = Config::get('moderation.resolver.user');

        if (is_subclass_of($userResolver, UserResolver::class)) {
            return call_user_func([$userResolver, 'resolve']);
        }

        throw new ModerationException('Invalid UserResolver implementation');
    }

    /**
     * Resolve the URL.
     *
     * @throws ModerationException
     *
     * @return string
     */
    protected function resolveUrl(): string
    {
        $urlResolver = Config::get('moderation.resolver.url');

        if (is_subclass_of($urlResolver, UrlResolver::class)) {
            return call_user_func([$urlResolver, 'resolve']);
        }

        throw new ModerationException('Invalid UrlResolver implementation');
    }

    /**
     * Resolve the IP Address.
     *
     * @throws ModerationException
     *
     * @return string
     */
    protected function resolveIpAddress(): string
    {
        $ipAddressResolver = Config::get('moderation.resolver.ip_address');

        if (is_subclass_of($ipAddressResolver, IpAddressResolver::class)) {
            return call_user_func([$ipAddressResolver, 'resolve']);
        }

        throw new ModerationException('Invalid IpAddressResolver implementation');
    }

    /**
     * Resolve the User Agent.
     *
     * @throws ModerationException
     *
     * @return string|null
     */
    protected function resolveUserAgent()
    {
        $userAgentResolver = Config::get('moderation.resolver.user_agent');

        if (is_subclass_of($userAgentResolver, UserAgentResolver::class)) {
            return call_user_func([$userAgentResolver, 'resolve']);
        }

        throw new ModerationException('Invalid UserAgentResolver implementation');
    }


    /**
     * {@inheritdoc}
     */
    public function toModerate(): array
    {
        if (!$this->readyForModerating()) {
            throw new ModerationException('A valid moderation event has not been set');
        }

        $attributeGetter = $this->resolveAttributeGetter($this->moderationEvent);

        if (!method_exists($this, $attributeGetter)) {
            throw new ModerationException(sprintf(
                'Unable to handle "%s" event, %s() method missing',
                $this->moderationEvent,
                $attributeGetter
            ));
        }

        $this->resolveModerationExclusions();

        list($old, $new) = $this->$attributeGetter();

        if ($this->getAttributeModifiers()) {
            foreach ($old as $attribute => $value) {
                $old[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }

            foreach ($new as $attribute => $value) {
                $new[$attribute] = $this->modifyAttributeValue($attribute, $value);
            }
        }

        $morphPrefix = Config::get('moderation.user.morph_prefix', 'user');

        $user = $this->resolveUser();


        return $this->transformModeration([
            'old_values'         => $old,
            'new_values'         => $new,
            'event'              => $this->moderationEvent,
            'moderatable_id'       => $this->getKey(),
            'moderatable_type'     => $this->getMorphClass(),
            $morphPrefix . '_id'   => $user ? $user->getAuthIdentifier() : null,
            $morphPrefix . '_type' => $user ? $user->getMorphClass() : null,
            'url'                => $this->resolveUrl(),
            'ip_address'         => $this->resolveIpAddress(),
            'user_agent'         => $this->resolveUserAgent(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function transformModeration(array $data): array
    {
        $moderation = [
            'user_id' => $data['user_id'],
            'user_type' => $data['user_type'],
            'entity_id' => $data['moderatable_id'],
            'entity_type' => $data['moderatable_type'],
            'status' => 'pending',
            'url' => $data['url'],
            'ip_address' => $data['ip_address'],
            'user_agent' => $data['user_agent'],
            'event' => $data['event'],
            'moderated_by' => null
        ];

        $moderation_fields = [];
        $values = count($data['old_values']) > 0 ? $data['old_values'] : $data['new_values'];
        foreach ($values as $key => $value) {
            $moderation_fields[$key]['name'] = $key;
            $moderation_fields[$key]['old'] = $data['old_values'][$key] ?? null;
            $moderation_fields[$key]['new'] = $data['new_values'][$key] ?? null;
            $moderation_fields[$key]['status'] = 'pending';
        }

        $data = [
            'moderation' => $moderation,
            'moderation_fields' => $moderation_fields
        ];

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationStrict(): bool
    {
        return $this->moderatioStrict ?? Config::get('moderation.strict', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getModerationThreshold(): int
    {
        return $this->moderationThreshold ?? Config::get('moderation.threshold', 0);
    }

    /**
     * Resolve the Moderatable attributes to exclude from the Moderation.
     *
     * @return void
     */
    protected function resolveModerationExclusions()
    {
        $this->excludedAttributes = $this->getModerationExclude();

        // When in strict mode, hidden and non visible attributes are excluded
        if ($this->getModerationStrict()) {
            // Hidden attributes
            $this->excludedAttributes = array_merge($this->excludedAttributes, $this->hidden);

            // Non visible attributes
            if ($this->visible) {
                $invisible = array_diff(array_keys($this->attributes), $this->visible);

                $this->excludedAttributes = array_merge($this->excludedAttributes, $invisible);
            }
        }

        // Exclude Timestamps
        if (!$this->getModerationTimestamps()) {
            array_push($this->excludedAttributes, $this->getCreatedAtColumn(), $this->getUpdatedAtColumn());

            if (in_array(SoftDeletes::class, class_uses_recursive(get_class($this)))) {
                $this->excludedAttributes[] = $this->getDeletedAtColumn();
            }
        }

        // Valid attributes are all those that made it out of the exclusion array
        $attributes = Arr::except($this->attributes, $this->excludedAttributes);

        foreach ($attributes as $attribute => $value) {
            // Apart from null, non scalar values will be excluded
            if (is_array($value) || (is_object($value) && !method_exists($value, '__toString'))) {
                $this->excludedAttributes[] = $attribute;
            }
        }
    }

    /**
     * Get the old/new attributes of a retrieved event.
     *
     * @return array
     */
    protected function getRetrievedEventAttributes(): array
    {
        // This is a read event with no attribute changes,
        // only metadata will be stored in the Audit

        return [
            [],
            [],
        ];
    }

    /**
     * Get the old/new attributes of a created event.
     *
     * @return array
     */
    protected function getCreatedEventAttributes(): array
    {
        $new = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeModeratable($attribute)) {
                $new[$attribute] = $value;
            }
        }

        return [
            [],
            $new,
        ];
    }

    /**
     * Get the old/new attributes of an updated event.
     *
     * @return array
     */
    protected function getUpdatedEventAttributes(): array
    {
        $old = [];
        $new = [];

        foreach ($this->getDirty() as $attribute => $value) {
            if ($this->isAttributeModeratable($attribute)) {
                $old[$attribute] = Arr::get($this->original, $attribute);
                $new[$attribute] = Arr::get($this->attributes, $attribute);
            }
        }

        return [
            $old,
            $new,
        ];
    }

    /**
     * Get the old/new attributes of a deleted event.
     *
     * @return array
     */
    protected function getDeletedEventAttributes(): array
    {
        $old = [];

        foreach ($this->attributes as $attribute => $value) {
            if ($this->isAttributeModeratable($attribute)) {
                $old[$attribute] = $value;
            }
        }

        return [
            $old,
            [],
        ];
    }

    /**
     * Get the old/new attributes of a restored event.
     *
     * @return array
     */
    protected function getRestoredEventAttributes(): array
    {
        // A restored event is just a deleted event in reverse
        return array_reverse($this->getDeletedEventAttributes());
    }


    /**
     * {@inheritdoc}
     */
    public function transitionTo(Contracts\Moderation $moderation, bool $old = false): Contracts\Moderatable
    {
        // The Moderation must be for an Moderatable model of this type
        if ($this->getMorphClass() !== $moderation->entity_type) {
            throw new ModeratableTransitionException(sprintf(
                'Expected Moderatable type %s, got %s instead',
                $this->getMorphClass(),
                $moderation->entity_type
            ));
        }

        // The Moderation must be for this specific Moderatable model
        if ($this->getKey() !== $moderation->entity_id) {
            throw new ModeratableTransitionException(sprintf(
                'Expected Moderatable id %s, got %s instead',
                $this->getKey(),
                $moderation->entity_id
            ));
        }

        // Redacted data should not be used when transitioning states
        foreach ($this->getAttributeModifiers() as $attribute => $modifier) {
            if (is_subclass_of($modifier, AttributeRedactor::class)) {
                throw new ModeratableTransitionException('Cannot transition states when an AttributeRedactor is set');
            }
        }

        // The attribute compatibility between the Audit and the Moderatable model must be met
        $modified = $moderation->getModified();

        if ($incompatibilities = array_diff_key($modified, $this->getAttributes())) {
            throw new ModeratableTransitionException(sprintf(
                'Incompatibility between [%s:%s] and [%s:%s]',
                $this->getMorphClass(),
                $this->getKey(),
                get_class($moderation),
                $moderation->getKey()
            ), array_keys($incompatibilities));
        }

        $key = $old ? 'old' : 'new';

        foreach ($modified as $attribute => $value) {
            if (array_key_exists($key, $value)) {
                $this->setAttribute($attribute, $value[$key]);
            }
        }

        return $this;
    }
}
