<?php

namespace App\Rules;

use App\Support\Money;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use InvalidArgumentException;

class PositiveMoney implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void 
    {
        if (!is_string($value)) {
            $fail("O campo {$attribute} deve ser enviado como uma string decimal.");

            return;
        }

        try {
            Money::toCents($value);
        } catch (InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}