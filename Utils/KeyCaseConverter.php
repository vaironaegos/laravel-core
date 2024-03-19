<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Utils;

use Illuminate\Support\Str;
use InvalidArgumentException;

trait KeyCaseConverter
{
    public function convert(string $case, $data): array
    {
        if (!in_array($case, ['camel', 'snake'])) {
            throw new InvalidArgumentException('Case must be either snake or camel');
        }

        if (!is_array($data)) {
            return $data;
        }

        $array = [];

        foreach ($data as $key => $value) {
            $array[Str::{$case}($key)] = is_array($value)
                ? $this->convert($case, $value)
                : $value;
        }

        return $array;
    }
}
