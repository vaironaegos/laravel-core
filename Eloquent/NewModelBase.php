<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent;

use DateTimeImmutable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Astrotech\Core\Base\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
    protected $fillable = [
        'id'
    ];

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
