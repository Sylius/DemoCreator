<?php

declare(strict_types=1);

namespace App\Exception;

final class DemoDeploymentException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $step
    ) {
        parent::__construct($message);
    }
}
