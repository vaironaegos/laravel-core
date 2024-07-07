<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Validators;

use Closure;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

final class UnrepeatableValidator implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly int|string|null $excludeId = null
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = DB::table($this->table)
            ->select('id')
            ->where($this->column, $value);

        if ($this->excludeId) {
            $query->where('id', '<>', Uuid::fromString($this->excludeId)->getBytes());
        }

        /** @todo rule to cover external_id of the NewModelBase */

        if ($query->exists()) {
            $fail("unrepeatableField");
        }
    }
}
