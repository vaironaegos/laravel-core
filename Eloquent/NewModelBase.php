<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Astrotech\Core\Laravel\Utils\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Astrotech\Core\Base\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotech\Core\Laravel\Eloquent\Casts\UuidToIdCast;

/**
 * Base class for new Eloquent models.
 * This class extends the base Model class and provides additional functionalities for models in the application.
 * It includes methods for handling fillable fields, validation, events, and casting.
 *
 * @package App\Models
 *
 * @property int $id
 * @property string $external_id
 *
 * @method static Builder activatedAndNotDeleted()
 */
abstract class NewModelBase extends Model
{
    use HasFactory;
    use HasEvents;

    public const CREATED_BY = 'created_by';
    public const UPDATED_BY = 'updated_by';
    public const DELETED_BY = 'deleted_by';
    public const DELETED_AT = 'deleted_at';

    public static $snakeAttributes = false;
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [];

    /**
     * @var string[]
     */
    protected $appends = [];

    protected array $rules = [];

    protected bool $hasExternalId = true;

    /**
     * Custom validation messages should be implemented within this method.
     */
    abstract public function construct(): void;

    /**
     * Constructs the model.
     *
     * @param array $attributes Optional, default is [].
     * An array of model attributes.
     */
    public function __construct(array $attributes = [])
    {
        $this->populateFillableFields();
        $this->populateRulesFields();
        $this->construct();
        parent::__construct($attributes);
    }

    protected static function boot(): void
    {
        parent::boot();

        $beforeSaveCallback = function (self $model) {
            $model->construct();
            $model->populateBlameableAttributes();
            $model->populateTimestampsColumns();
            $model->attributes = $model->getAttributes();
            $model->beforeValidation();
            $validator = Validator::make($model->attributes, $model->rules);

            if (!$validator->fails()) {
                $model->beforeSave();
                return;
            }

            $details = [];
            $errors = $validator->errors()->toArray();
            $data = $model->getAttributes();
            foreach ($errors as $field => $message) {
                $value = $data[$field] ?? null;
                $details[] = ['field' => $field, 'error' => $message, 'value' => $value];
            }

            throw new ValidationException($details, 'Validation error in model "' . $model::class . '"');
        };

        $afterSaveCallback = function (self $model) {
            $model->attributes = $model->getAttributes();
            $model->afterSave();
        };

        $beforeDeleteCallback = function (self $model) {
            $blameName = 'anonymous';
            $now = new DateTimeImmutable();

            if ($model->exists && $model->hasModelAttribute(self::DELETED_BY)) {
                $model->setAttribute(static::DELETED_BY, $blameName);
            }

            if ($model->exists && $model->hasModelAttribute('deleted_at')) {
                $model->{static::DELETED_AT} = $now->format('Y-m-d H:i:s');
            }

            $model->beforeDelete();
        };

        self::creating($beforeSaveCallback);
        self::created($afterSaveCallback);
        self::updating($beforeSaveCallback);
        self::updated($afterSaveCallback);
        self::deleting($beforeDeleteCallback);
        self::saved($afterSaveCallback);
    }

    protected function beforeValidation(): void
    {
        foreach ($this->modelAttributes() as $fieldName) {
            $value = $this->getAttribute($fieldName);
            if (is_null($value)) {
                continue;
            }
            $this->setAttribute($fieldName, $value);
        }

        if (!empty($this->getAttribute('id'))) {
            return;
        }

        if ($this->hasExternalId && !$this->external_id) {
            $this->external_id = Uuid::uuid4()->toString();
        }
    }

    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if (isset($this->rules[$key]) && in_array('array', $this->rules[$key]) && is_string($value)) {
            return json_decode($value, true);
        }

        return parent::getAttribute($key);
    }

    /**
     * Invoked before the saving event of a model.
     * The instance of NewModelBase model.
     */
    protected function beforeSave(): void
    {
        foreach (array_keys($this->getAttributes()) as $attributeName) {
            if (!is_array($this->{$attributeName})) {
                continue;
            }

            $this->{$attributeName} = json_encode($this->{$attributeName});
        }
    }

    /**
     * Invoked after the saving event of a model.
     * The instance of NewModelBase model.
     */
    protected function afterSave(): void
    {
        Cache::put("{$this->getTable()}_{$this->external_id}", $this->getAttributes());
        Cache::delete("{$this->getTable()}_collection");
        Cache::delete("{$this->getTable()}_options");
        Cache::delPattern("{$this->getTable()}_search*");
    }

    /**
     * Invoked before the deleting event of a model.
     * The instance of NewModelBase model.
     */
    protected function beforeDelete(): void
    {
        Cache::delete("{$this->getTable()}_{$this->external_id}");
        Cache::delete("{$this->getTable()}_search*");
    }

    /**
     * Overrides the "fill" method of Laravel Eloquent.
     *
     * @param array $attributes
     * An array of attribute key-value pairs.
     *
     * @return static
     * @throws RuntimeException
     */
    public function fill(array $attributes): static
    {
        parent::fill($attributes);

        if (empty($attributes)) {
            return $this;
        }

        foreach ($attributes as $attributeName => $value) {
            $snakeCaseAttr = Str::snake($attributeName);

            if (!$this->hasModelAttribute($snakeCaseAttr)) {
                continue;
            }

            $attributes[$snakeCaseAttr] = $value;

            if (isset($this->rules[$snakeCaseAttr])) {
                $attrRule = $this->rules[$snakeCaseAttr];
                if (is_array($attrRule) && in_array('boolean', $this->rules[$snakeCaseAttr])) {
                    $attributes[$snakeCaseAttr] = convertToBool($value);
                }
            }
        }

        return parent::fill($attributes);
    }

    /**
     * Sets the fillable attributes to a combination of existing attributes and the new ones provided.
     *
     * @param array $attributes
     * An array of new attributes to be set for the model.
     */
    public function setDefaultAttributesValue(array $attributes): void
    {
        $this->fillable = [...$this->attributes, $attributes];
    }

    /**
     * Adds new fillable attributes to the existing ones.
     *
     * @param array $fillable
     * An array of new fillable attributes to be added for the model.
     */
    public function addFillable(array $fillable): void
    {
        $this->fillable = array_merge(['id', 'external_id'], $fillable);
    }

    /**
     * Add additional rules to the model.
     *
     * @param array $rules
     * The new rules array to be added.
     */
    public function addRules(array $rules): void
    {
        if ($this->hasModelAttribute('external_id')) {
            $this->rules['external_id'] = ['required', 'string', 'size:36'];
        }

        if ($this->hasModelAttribute('active')) {
            $this->rules['active'] = ['nullable', 'boolean'];
        }

        if ($this->hasModelAttribute('created_at')) {
            $this->rules['created_at'] = ['required', 'date'];
        }

        if ($this->hasModelAttribute('updated_at')) {
            $this->rules['updated_at'] = ['nullable', 'date'];
        }

        if ($this->hasModelAttribute('deleted_at')) {
            $this->rules['deleted_at'] = ['nullable', 'date'];
        }

        if ($this->hasModelAttribute('created_by')) {
            $this->rules['created_by'] = ['required', 'string', 'max:100'];
        }

        if ($this->hasModelAttribute('updated_by')) {
            $this->rules['updated_by'] = ['nullable', 'string', 'max:100'];
        }

        if ($this->hasModelAttribute('deleted_by')) {
            $this->rules['deleted_by'] = ['nullable', 'string', 'max:100'];
        }

        $this->rules = [...$this->rules, ...$rules];
    }

    /**
     * Add casting to the model.
     *
     * @param array $casts
     * The new casts array to be added.
     */
    public function addCast(array $casts): void
    {
        $this->casts['id'] = UuidToIdCast::class . ":{$this->getTable()}";

        if ($this->hasModelAttribute('active')) {
            $this->casts['active'] = 'boolean';
        }

        $this->casts = [...$this->casts, ...$casts];
    }

    /**
     * Add hidden columns to the model.
     *
     * @param array $hidden
     * The new hidden columns array to be added.
     */
    public function addHidden(array $hidden): void
    {
        $this->hidden = [...$this->hidden, ...$hidden];
    }

    /**
     * Add guarded columns to the model.
     *
     * @param array $guarded
     * The new guarded columns array to be added.
     */
    public function addGuarded(array $guarded): void
    {
        $this->guarded = [...$this->guarded, ...$guarded];
    }

    /**
     * Add appends columns to the model.
     *
     * @param array $appendFields
     * The new appends columns array to be added.
     */
    public function addAppends(array $appendFields): void
    {
        $this->appends = [...$this->appends, ...$appendFields];
    }

    /**
     * Register an event with a given handler.
     *
     * @param string $event
     * The name of the event to register.
     *
     * @param string $handler
     * The name of the handler to trigger when the event is dispatched.
     * @throws RuntimeException
     */
    public function addEvent(string $event, string $handler): void
    {
        $available = [
            'retrieved',
            'creating',
            'created',
            'updating',
            'updated',
            'saving',
            'saved',
            'deleting',
            'deleted',
            'restoring',
            'restored',
        ];

        if (!in_array($event, $available, true)) {
            throw new RuntimeException('Event not available', ['event' => $event]);
        }

        $this->dispatchesEvents[$event] = $handler;
    }

    /**
     * Get the model's attributes.
     *
     * @return array
     * The array containing the model's attributes.
     */
    public function modelAttributes(): array
    {
        $fillable = $this->fillable;
        $rules = array_keys($this->rules);
        $casts = array_keys($this->casts);
        return array_merge($fillable, $rules, $casts);
    }

    /**
     * Check if the model has an attribute.
     *
     * @param string $name
     * The name of the attribute.
     *
     * @return bool
     * Returns `true` if the attribute exists, `false` otherwise.
     */
    public function hasModelAttribute(string $name): bool
    {
        return in_array($name, $this->modelAttributes());
    }

    /**
     * Convert the current instance of the model to an array.
     *
     * @return array
     * The array form of the current instance.
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        foreach (array_keys($data) as $fieldName) {
            $data[$fieldName] = $this->getAttribute($fieldName);
        }

        $data['id'] = $data['external_id'] ?? null;
        unset($data['external_id'], $data['deleted_at'], $data['deleted_by']);

        foreach ($this->getCasts() as $fieldName => $castName) {
            if ($fieldName === 'id') {
                continue;
            }

            [$className,] = explode(':', $castName, 2);

            if ($className !== UuidToIdCast::class) {
                continue;
            }

            unset($data[$fieldName]);
        }

        return $data;
    }

    /**
     * Returns summarized data based on the return of the toArray() method
     *
     * @return array
     * Returns an array with the summarized data of the model.
     */
    public function toSoftArray(): array
    {
        return $this->returnOnlyFields(['id']);
    }

    /**
     * Checks if the model has been softly deleted.
     *
     * @return bool
     * Returns `true` if the model has been softly deleted, `false` otherwise.
     */
    public function isDeleted(): bool
    {
        return $this->hasModelAttribute('deleted_at') && !empty($this->deleted_at);
    }

    /**
     * Return only the fields specified in the parameters based on toArray() return
     *
     * @param string[] $fieldsToReturn
     * An array containing the fields to return.
     *
     * @return array
     * Returns an array containing only the specified fields.
     */
    protected function returnOnlyFields(array $fieldsToReturn): array
    {
        return array_filter($this->toArray(), function ($key) use ($fieldsToReturn) {
            return in_array($key, $fieldsToReturn);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Populates the timestamp columns (if they exist) in the model.
     * Sets the 'CREATED_AT' attribute to the current date and time if the model has not been persisted yet.
     * It also sets the 'UPDATED_AT' attribute to the current date and time.
     *
     * @return void
     */
    protected function populateTimestampsColumns(): void
    {
        $now = new DateTimeImmutable();

        if (!$this->exists && $this->hasModelAttribute(static::CREATED_AT)) {
            $this->{static::CREATED_AT} = $now->format('Y-m-d H:i:s');
        }

        if ($this->hasModelAttribute(static::UPDATED_AT)) {
            $this->{static::UPDATED_AT} = $now->format('Y-m-d H:i:s');
        }
    }

    /**
     * Populates the "blameable" model attributes ('CREATED_BY' and 'UPDATED_BY', if they exist) with the current
     * authenticated user's name and external ID.
     * If there's no authenticated user, it uses 'anonymous' instead.
     *
     * @return void
     */
    protected function populateBlameableAttributes(): void
    {
        /** @var ModelBase $user */
        $user = Auth::user();
        $blameName = 'anonymous';

        if ($user) {
            $blameName = "{$user->getAttribute('name')} [{$user->external_id}]";
        }

        if (!$this->exists && $this->hasModelAttribute(self::CREATED_BY)) {
            $this->setAttribute(static::CREATED_BY, $blameName);
        }

        if ($this->exists && $this->hasModelAttribute(self::UPDATED_BY)) {
            $this->setAttribute(static::UPDATED_BY, $blameName);
        }
    }

    /**
     * Adds 'CREATED_AT', 'UPDATED_AT', 'DELETED_AT', 'CREATED_BY', 'UPDATED_BY', 'DELETED_BY' and others fields if
     * necessary, to the list of fillable attributes, if these attributes exist in the model.
     *
     * @return void
     */
    private function populateFillableFields(): void
    {
        if ($this->hasModelAttribute(static::CREATED_AT)) {
            $this->fillable[] = static::CREATED_AT;
        }

        if ($this->hasModelAttribute(static::CREATED_BY)) {
            $this->fillable[] = static::CREATED_BY;
        }

        if ($this->hasModelAttribute(static::UPDATED_AT)) {
            $this->fillable[] = static::UPDATED_AT;
        }

        if ($this->hasModelAttribute(static::UPDATED_BY)) {
            $this->fillable[] = static::UPDATED_BY;
        }

        if ($this->hasModelAttribute(static::DELETED_AT)) {
            $this->fillable[] = static::DELETED_AT;
        }

        if ($this->hasModelAttribute(static::DELETED_BY)) {
            $this->fillable[] = static::DELETED_BY;
        }
    }

    /**
     * Sets validation rules for 'CREATED_AT', 'CREATED_BY', 'UPDATED_AT', 'UPDATED_BY', 'DELETED_AT', 'DELETED_BY' and
     * others fields if necessary, attributes if they exist in the model.
     *
     * @return void
     */
    private function populateRulesFields(): void
    {
        if ($this->hasModelAttribute(static::CREATED_AT)) {
            $this->rules[static::CREATED_AT] = ['required', 'date'];
        }

        if ($this->hasModelAttribute(static::CREATED_BY)) {
            $this->rules[static::CREATED_BY] = ['required', 'string'];
        }

        if ($this->hasModelAttribute(static::UPDATED_AT)) {
            $this->rules[static::UPDATED_AT] = ['nullable', 'date'];
        }

        if ($this->hasModelAttribute(static::UPDATED_BY)) {
            $this->rules[static::UPDATED_BY] = ['nullable', 'string'];
        }

        if ($this->hasModelAttribute(static::DELETED_AT)) {
            $this->rules[static::DELETED_AT] = ['nullable', 'date'];
        }

        if ($this->hasModelAttribute(static::DELETED_BY)) {
            $this->rules[static::DELETED_BY] = ['nullable', 'string'];
        }
    }

    public static function tableName(): string
    {
        return Str::snake(Str::pluralStudly(class_basename(static::class)));
    }

    public static function findByExternalId(string $externalId): ?self
    {
        $model = new static();
        $cacheKey = "{$model->getTable()}_{$externalId}";

        if (Cache::has($cacheKey)) {
            $model->fill(Cache::get($cacheKey));
            return $model;
        }

        $query = $model->where('external_id', $externalId);

        if ($model->hasModelAttribute('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($model->hasModelAttribute('deleted_by')) {
            $query->whereNull('deleted_by');
        }

        $record = $query->first();

        if ($record) {
            Cache::forever($cacheKey, $record->getAttributes());
        }
        
        return $record;
    }


    public static function getIdFromExternalId(string $externalId): ?int
    {
        $model = new static();
        $query = $model->select('id')->where('external_id', $externalId);

        if ($model->hasModelAttribute('deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($model->hasModelAttribute('deleted_by')) {
            $query->whereNull('deleted_by');
        }

        return $query->first()?->id;
    }

    public function scopeActivatedAndNotDeleted(Builder $query): Builder
    {
        return $query->where('active', 1)
            ->whereNull('deleted_at')
            ->whereNull('deleted_by');
    }
}
