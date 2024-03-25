<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use Astrotech\Core\Laravel\Eloquent\Casts\EfficientUuidCast;
use Ramsey\Uuid\Uuid;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Astrotech\Core\Base\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

abstract class ModelBase extends Model
{
    use HasFactory;
    use HasEvents;

    public const CREATED_BY = 'created_by';
    public const UPDATED_BY = 'updated_by';
    public const DELETED_BY = 'deleted_by';
    public const DELETED_AT = 'deleted_at';

    protected $keyType = 'string';
    public $incrementing = false;
    public static $snakeAttributes = false;
    public $timestamps = false;

    /**
     * @var string[]
     */
    protected $fillable = [
        'id'
    ];

    protected $casts = [
        'id' => EfficientUuidCast::class,
    ];

    /**
     * @var string[]
     */
    protected array $virtualAttributes = [];

    protected array $rules = [];

    abstract public function construct(): void;

    public function __construct(array $attributes = [])
    {
        $this->construct();
        parent::__construct($attributes);

        $beforeSaveCallback = function (self $model) {
            $model->populateBlameableAttributes();
            $model->populateTimestampsColumns();

            $this->attributes = $model->getRecordAttributes();
            $rules = $model->getRules();
            $validator = Validator::make($this->attributes, $rules);

            $this->beforeSave($model);

            if (!$validator->fails()) {
                return;
            }

            $details = [];
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                $value = $data[$field] ?? null;
                $details[] = ['field' => $field, 'error' => $message, 'value' => $value];
            }

            throw new ValidationException($details, 'Validation error occurred in "' . $model::class . '"');
        };

        $afterSaveCallback = function (self $model) {
            $model->attributes = $model->getRecordAttributes(false);
            $this->afterSave($model);
        };

        $beforeDeleteCallback = function (self $model) {
            $blameName = 'anonymous';
            $now = new DateTimeImmutable();

            if ($this->exists && $this->hasModelAttribute(self::DELETED_BY)) {
                $this->setAttribute(static::DELETED_BY, $blameName);
            }

            if ($this->exists && $this->hasModelAttribute('deleted_at')) {
                $this->{static::DELETED_AT} = $now->format('Y-m-d H:i:s');
            }

            $this->beforeDelete($model);
        };

        static::creating($beforeSaveCallback);
        static::created($afterSaveCallback);
        static::updating($beforeSaveCallback);
        static::updated($afterSaveCallback);
        static::deleting($beforeDeleteCallback);
    }

    public function beforeSave(ModelBase $model): void
    {
        if ($model->id === null || empty($model->id)) {
            $model->id = Uuid::uuid4()->toString();
        }
    }

    public function afterSave(ModelBase $model): void
    {
    }

    public function beforeDelete(ModelBase $model): void
    {
    }

    public function fill(array $attributes): static
    {
        if (empty($attributes)) {
            return parent::fill($attributes);
        }

        $this->refreshVirtualColumns();

        foreach ($attributes as $attributeName => $value) {
            $snakeCaseAttr = Str::snake($attributeName);
            $attributes[$snakeCaseAttr] = $value;

            $rules = $this->getRules();
            if (isset($rules[$snakeCaseAttr])) {
                $attrRule = $rules[$snakeCaseAttr];
                if (is_string($value) && is_array($attrRule) && in_array('json', $attrRule)) {
                    $attributes[$snakeCaseAttr] = json_decode($value, true);
                }

                if (is_array($attrRule) && in_array('boolean', $rules[$snakeCaseAttr])) {
                    $attributes[$snakeCaseAttr] = convertToBool($value);
                }
            }
        }

        return parent::fill($attributes);
    }

    protected function refreshVirtualColumns(): void
    {
        if (empty($this->attributes)) {
            return;
        }

        $casters = $this->getCasts();

        foreach ($this->attributes as $name => $value) {
            foreach ($casters as $fieldName => $caster) {
                if ($fieldName !== $name) {
                    continue;
                }

                if ($caster !== EfficientUuidCast::class) {
                    continue;
                }

                $columnName = "raw_{$fieldName}";

                if (isUuidString($value)) {
                    $value = Uuid::fromString($value)->toString();
                }

                $this->attributes[$columnName] = $value;
                $this->virtualAttributes[$columnName] = $value;
                $this->fillable[] = $columnName;
                $this->fillable = array_unique($this->fillable);
            }
        }
    }

    private function getRules(): array
    {
        $rules = [];
        foreach ($this->rules as $field => $rule) {
            $unique = "unique:{$this->table},{$field},{id}";
            $id = $this->getAttribute('id');
            $search = ['unique', ',{id}'];
            $replace = [$unique, $id ? ",{$this->getAttribute('id')}" : ''];
            $rules[$field] = str_replace($search, $replace, $rule);
        }
        return $rules;
    }

    public function uuidColumn(): string
    {
        return $this->primaryKey;
    }

    public function addFillable(array $fillable): void
    {
        $this->fillable = [...$this->fillable, ...$fillable];
    }

    public function addGuarded(array $guarded): void
    {
        $this->guarded = [...$this->guarded, ...$guarded];
    }

    public function addCast(array $casts): void
    {
        $this->casts = [...$this->casts, ...$casts];
    }

    public function addHidden(array $hidden): void
    {
        $this->hidden = [...$this->hidden, ...$hidden];
    }

    public function addRules(array $rules): void
    {
        $this->rules = [...$this->rules, ...$rules];
    }

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

    private function populateTimestampsColumns(): void
    {
        $now = new DateTimeImmutable();

        if (!$this->exists && $this->hasModelAttribute(static::CREATED_AT)) {
            $this->{static::CREATED_AT} = $now->format('Y-m-d H:i:s');
        }

        if ($this->hasModelAttribute(static::UPDATED_AT)) {
            $this->{static::UPDATED_AT} = $now->format('Y-m-d H:i:s');
        }
    }

    private function populateBlameableAttributes(): void
    {
        /** @var ModelBase $user */
        $user = auth('api')->user();
        $blameName = 'anonymous';

        if ($user) {
            $blameName = "{$user->getAttribute('name')} [{$user->id}]";
        }

        if (!$this->exists && $this->hasModelAttribute(self::CREATED_BY)) {
            $this->setAttribute(static::CREATED_BY, $blameName);
        }

        if ($this->exists && $this->hasModelAttribute(self::UPDATED_BY)) {
            $this->setAttribute(static::UPDATED_BY, $blameName);
        }
    }

    public function getRecordAttributes(bool $removeVirtuals = true): array
    {
        $attributes = parent::getAttributes();

        if ($removeVirtuals || empty($attributes)) {
            return array_filter(
                $attributes,
                fn(string $fieldName) => !array_key_exists($fieldName, $this->virtualAttributes),
                ARRAY_FILTER_USE_KEY
            );
        }

        $this->refreshVirtualColumns();
        return array_merge($attributes, $this->virtualAttributes);
    }

    public function modelAttributes(): array
    {
        $fillable = $this->fillable;
        $rules = array_keys($this->rules);
        $casts = array_keys($this->casts);
        $virtualColums = array_keys($this->virtualAttributes);
        $validAttributes = array_merge($fillable, $rules, $casts);
        return array_diff($validAttributes, $virtualColums);
    }

    public function hasModelAttribute(string $name): bool
    {
        return in_array($name, $this->modelAttributes());
    }

    public function hasMany($related, $foreignKey = null, $localKey = null): HasMany
    {
        if (empty($this->attributes)) {
            return parent::hasMany($related, $foreignKey, $localKey);
        }

        $this->refreshVirtualColumns();
        $localKey = !is_null($localKey) ? 'raw_' . $localKey : null;
        return parent::hasMany($related, $foreignKey, $localKey);
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null, $relation = null): BelongsTo
    {
        if (empty($this->attributes)) {
            return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
        }

        $this->refreshVirtualColumns();
        $foreignKey = !is_null($foreignKey) ? 'raw_' . $foreignKey : null;
        return parent::belongsTo($related, $foreignKey, $ownerKey, $relation);
    }

    public function hasOne($related, $foreignKey = null, $localKey = null): HasOne
    {
        if (empty($this->attributes)) {
            return parent::hasOne($related, $foreignKey, $localKey);
        }

        $this->refreshVirtualColumns();
        $localKey = !is_null($localKey) ? 'raw_' . $localKey : null;
        return parent::hasOne($related, $foreignKey, $localKey);
    }

    public function toArray(): array
    {
        return array_filter(
            parent::toArray(),
            fn(string $fieldName) => !str_contains($fieldName, "raw_"),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function toSoftArray(): array
    {
        $data = $this->toArray();
        return ['id' => $data['id']];
    }

    public function isDeleted(): bool
    {
        return $this->hasModelAttribute('deleted_at') && !empty($this->deleted_at);
    }

    public function queryWithoutDataSoftDeleted()
    {
        $query = $this::query();
        if ($this->hasModelAttribute('deleted_at')) {
            $query->whereNull('deleted_at');
        }
        return $query;
    }
}
