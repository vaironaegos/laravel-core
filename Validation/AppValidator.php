<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Validation;

use Astrotech\Core\Base\Adapter\Contracts\Dto;

final class AppValidator
{
    private array $errors = [];

    public function validate(array $values, array $rules): bool
    {
        if (empty($values)) {
            return true;
        }

        foreach ($values as $fieldName => $value) {
            foreach ($rules as $collection) {
                /** @var ValidatorField $validator */
                foreach ($collection as $validator) {
                    if ($validator->getFieldName() !== $fieldName) {
                        continue;
                    }

                    if (!$validator instanceof RequiredValidator && is_null($value)) {
                        continue;
                    }

                    $result = $validator->validate($value);

                    if (count($result) === 0) {
                        continue;
                    }

                    $this->errors[] = $result;
                }
            }
        }

        return $this->isValid();
    }

    public function validateDto(Dto $dto): bool
    {
        return $this->validate($dto->values(), $dto->rules());
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
