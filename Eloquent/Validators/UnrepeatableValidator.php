<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Validators;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Validation\ValidationRule;

final class UnrepeatableValidator implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column,
        private readonly int|string|null $excludeId = null,
        private readonly bool $excludeSoftDeleted = false
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_null($value)) {
            return;
        }

        $query = DB::table($this->table)
            ->select('id')
            ->where($this->column, $value);

        if ($this->excludeId) {
            $query->where('id', '<>', $this->excludeId);
        }

        if ($this->excludeSoftDeleted) {
            $query->whereNull('deleted_at');
        }

        if ($query->exists()) {
            $fail("unrepeatableField");
        }
    }
}
