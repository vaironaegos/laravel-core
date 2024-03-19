<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Validation;

use Astrotech\Core\Base\Adapter\Contracts\Dto;
use Astrotech\Core\Base\Exception\ValidationException;
use Illuminate\Support\Facades\Validator as FacadesValidator;

final class LaravelValidator
{
    private array $errors = [];

    public function validate(array $values, array $rules): bool
    {
        return $this->isValid();
    }

    public function validateDto(Dto $dto): bool
    {
        return $this->validate($dto->values(), $dto->rules());
    }

    public function make(
        array $data,
        array $rules,
        array $messages = [],
        array $customAttributes = []
    ): void {

        $validator = FacadesValidator::make(
            $data,
            $rules,
            $messages,
            $customAttributes
        );

        if ($validator->fails()) {
            $errors = $validator->errors()->toArray();
            foreach ($errors as $field => $message) {
                $value = $data[$field] ?? null;
                $this->errors = ['field' => $field,
                    'error' => underscoreToCamelCase(str_replace('.', '_', $message[0])),
                    'value' => $value];

                if (!$this->isValid()) {
                    throw new ValidationException($this->getErrors());
                }
            }
        }
    }

    public function isValid(): bool
    {
        return count($this->errors) === 0;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
