<?php

declare(strict_types=1);

namespace App\StoreDesigner\Dto;

final readonly class ChatConversationDto implements \JsonSerializable
{
    public function __construct(
        public string $conversationId,
        public array $messages = [],
        public ?StoreDetailsDto $storeDetails = null,
        public ChatConversationState $state = ChatConversationState::Collecting,
        public ?string $error = null,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'conversationId' => $this->conversationId,
            'messages' => $this->messages,
            'storeDetails' => $this->storeDetails,
            'state' => $this->state->value,
            'error' => $this->error,
        ];
    }
}
