<?php

declare(strict_types=1);

namespace Astrotech\Core\Laravel\Eloquent\Validators;

use Illuminate\Contracts\Validation\ValidationRule;

final class CpfCnpjValidator implements ValidationRule
{
    public function validate(string $attribute, $value, $fail): void
    {
        $value = trim(preg_replace('/\D/', '', $value));

        if (strlen($value) === 11 && !$this->validateCpf($value)) {
            $fail('invalidCpf');
            return;
        }

        if (strlen($value) === 14 && !$this->validateCnpj($value)) {
            $fail('invalidCnpj');
        }
    }

    protected function validateCpf(string $cpf): bool
    {
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;

            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    protected function validateCnpj(string $cnpj): bool
    {
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        $sum = 0;
        $weight = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weight[$i];
        }

        $digit = $sum % 11;
        if ($digit < 2) {
            $digit = 0;
        } else {
            $digit = 11 - $digit;
        }

        if ($cnpj[12] != $digit) {
            return false;
        }

        $sum = 0;
        $weight = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weight[$i];
        }

        $digit = $sum % 11;
        if ($digit < 2) {
            $digit = 0;
        } else {
            $digit = 11 - $digit;
        }

        return $cnpj[13] == $digit;
    }
}
