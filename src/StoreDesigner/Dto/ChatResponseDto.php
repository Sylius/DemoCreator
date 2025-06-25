<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class ChatResponseDto
{
    public function __construct(
        public string $conversationId,
        public array $messages = [],
        public ?StoreConfigurationDto $storeConfiguration = null,
        public ?array $fixtures = null,
        public ?bool $dataCompleted = null,
    ) {
    }
}
