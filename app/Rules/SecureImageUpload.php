<?php

namespace App\Rules;

use App\Services\SecureImageStorage;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SecureImageUpload implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = SecureImageStorage::validationError($value);

        if ($error !== null) {
            $fail($error);
        }
    }
}
