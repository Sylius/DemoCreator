<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class ChatResponseDto
{
    public function __construct(
        public string $conversationId,
        public array $messages,
        public bool $dataCompleted,
        public StoreConfigurationDto $storeConfiguration,
        public ?array $fixtures,
    ) {
    }
}
