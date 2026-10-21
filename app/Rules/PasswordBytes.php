<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Bcrypt uses at most 72 bytes; validate before hashing, including UTF-8 input. */
class PasswordBytes implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) > 72) $fail('Use a password containing at most 72 bytes. Accented characters can use more than one byte.');
    }
}
