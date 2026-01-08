<?php

namespace Jtech\Debug;

use Exception;

class ValidationException extends \Illuminate\Validation\ValidationException
{
    protected array $errors;
    protected array $input;

    public function __construct(array $errors, array $input = [])
    {
        $this->errors = $errors;
        $this->input  = $input;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function input(): array
    {
        return $this->input;
    }
}
