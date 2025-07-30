<?php

declare(strict_types=1);

namespace App\StoreDeployer\Exception;

final class InvalidStorePresetException extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
