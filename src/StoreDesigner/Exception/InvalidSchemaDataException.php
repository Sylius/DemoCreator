<?php

namespace App\StoreDesigner\Exception;

class InvalidSchemaDataException extends \InvalidArgumentException
{
    public function __construct(
        string $message,
        public readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
} 