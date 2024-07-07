<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use ReflectionClass;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Astrotech\Core\Base\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Relations\Relation;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotech\Core\Laravel\Eloquent\Casts\UuidToIdCast;

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
    protected $fillable = ['id', 'external_id'];

    protected array $rules = [];

    abstract public function construct(): void;

    public function __construct(array $attributes = [])
    {
        $this->populateFillableFields();
        $this->populateRulesFields();
        $this->construct();
        parent::__construct($attributes);

        $beforeSaveCallback = function (self $model) {
            $model->construct();
            $model->populateBlameableAttributes();
            $model->populateTimestampsColumns();
            $model->attributes = $model->getAttributes();
            $validator = Validator::make($model->attributes, $model->rules);

            if (!$validator->fails()) {
                $model->beforeSave($model);
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

    protected function beforeSave(NewModelBase $model): void
    {
        if (!empty($model->getAttribute('id'))) {
            return;
        }

        $model->external_id = Uuid::uuid4()->toString();
    }

    protected function afterSave(NewModelBase $model): void
    {
    }

    protected function beforeDelete(NewModelBase $model): void
    {
    }

    public function fill(array $attributes): static
    {
        if (empty($attributes)) {
            return parent::fill($attributes);
        }

        foreach ($attributes as $attributeName => $value) {
            $snakeCaseAttr = Str::snake($attributeName);
            $attributes[$snakeCaseAttr] = $value;

            if (isset($rules[$snakeCaseAttr])) {
                $attrRule = $rules[$snakeCaseAttr];
                if (is_string($value) && is_array($attrRule) && in_array('json', $attrRule)) {
                    $attributes[$snakeCaseAttr] = json_decode($value, true);
                }

                if (is_array($attrRule) && in_array('boolean', $this->rules[$snakeCaseAttr])) {
                    $attributes[$snakeCaseAttr] = convertToBool($value);
                }
            }
        }

        return parent::fill($attributes);
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
            $blameName = "{$user->getAttribute('name')} [{$user->external_id}]";
        }

        if (!$this->exists && $this->hasModelAttribute(self::CREATED_BY)) {
            $this->setAttribute(static::CREATED_BY, $blameName);
        }

        if ($this->exists && $this->hasModelAttribute(self::UPDATED_BY)) {
            $this->setAttribute(static::UPDATED_BY, $blameName);
        }
    }

    public function modelAttributes(): array
    {
        $fillable = $this->fillable;
        $rules = array_keys($this->rules);
        $casts = array_keys($this->casts);
        return array_merge($fillable, $rules, $casts);
    }

    public function hasModelAttribute(string $name): bool
    {
        return in_array($name, $this->modelAttributes());
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        $data['id'] = $data['external_id'] ?? null;
        unset($data['external_id']);

        foreach ($this->getCasts() as $fielName => $castName) {
            [$className,] = explode(':', $castName, 2);

            if ($className !== UuidToIdCast::class) {
                continue;
            }

            unset($data[$fielName]);
        }

        return $data;
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

    public function getRelationsList(): array
    {
        $model = new ReflectionClass($this);
        $methods = $model->getMethods(ReflectionMethod::IS_PUBLIC);
        $relations = [];

        foreach ($methods as $method) {
            if ($method->class !== $model->getName() || !$method->hasReturnType()) {
                continue;
            }

            $returnType = $method->getReturnType()->getName();

            if (!is_subclass_of($returnType, Relation::class)) {
                continue;
            }

            $relationType = lcfirst(basename(str_replace('\\', '/', $returnType)));
            $relations[$relationType][] = $method->name;
        }

        return $relations;
    }

    protected function returnOnlyFields(array $fieldsToReturn): array
    {
        return array_filter($this->toArray(), function ($key) use ($fieldsToReturn) {
            return in_array($key, $fieldsToReturn);
        }, ARRAY_FILTER_USE_KEY);
    }

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
}
