<?php

namespace App\StoreDesigner\Exception;

class OpenAiApiException extends \RuntimeException
{
    private int $httpStatus;

    public function __construct(string $message = '', int $httpStatus = 500, ?\Throwable $previous = null)
    {
        if (str_contains($message, 'Your organization must be verified to use the model')) {
            $message = json_decode($message, true)['error']['message'] ?? $message;
        }

        parent::__construct($message, 0, $previous);
        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
