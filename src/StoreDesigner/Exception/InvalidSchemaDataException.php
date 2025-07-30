<?php

namespace App\StoreDesigner\Exception;

class InvalidSchemaDataException extends \InvalidArgumentException
{
    public function __construct(
        public readonly array $errors = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(json_encode($this->errors), $code, $previous);
    }
}
