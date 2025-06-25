<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class ChatRequestDto
{
    public function __construct(
        public string $message,
        public ?array $history = [],
        public ?StoreConfigurationDto $storeConfiguration = null,
        public ?array $fixtures = null
    ) {}
}