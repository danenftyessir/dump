<?php

namespace Exception;

use Exception as BaseException;

class ValidationException extends BaseException
{
    private $errors;

    // Ctor
    public function __construct($message = "Validation failed", $errors = [], $code = 422, BaseException $previous = null) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    // Get Errors
    public function getErrors() {
        return $this->errors;
    }
}