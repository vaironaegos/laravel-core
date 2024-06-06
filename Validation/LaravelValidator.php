<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Validation;

use Astrotech\Core\Base\Exception\ValidationException;
use Astrotech\Core\Base\Adapter\Contracts\ValidatorInterface;
use Illuminate\Support\Facades\Validator as FacadesValidator;

final class LaravelValidator implements ValidatorInterface
{
    private array $errors = [];

    public static function validate(string $field, mixed $value, string $validationRule): void
    {
        $validator = FacadesValidator::make(
            [$field => $value],
            [$field => $validationRule],
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                throw new ValidationException([
                    'field' => $field,
                    'error' => underscoreToCamelCase(str_replace('.', '_', $message[0])),
                    'value' => $value
                ]);
            }
        }
    }

    public static function validateBatch(array $values, array $validationRules): void
    {
        $validator = FacadesValidator::make(
            $values,
            $validationRules,
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                throw new ValidationException([
                    'field' => $field,
                    'error' => underscoreToCamelCase(str_replace('.', '_', $message[0])),
                    'value' => isset($values[$field]) ? $values[$field] : ''
                ]);
            }
        }
    }
}
