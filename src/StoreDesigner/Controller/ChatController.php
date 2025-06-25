<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\ChatRequestDto;
use App\StoreDesigner\Service\ChatConversationService;
use App\StoreDesigner\Service\StoreFixtureProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractController
{
    public function __construct(
        private readonly ChatConversationService $chatConversationService,
        private readonly StoreFixtureProcessor $storeFixtureProcessor
    ) {}

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(ChatRequestDto $chatRequest): JsonResponse
    {
        $chatResponse = $this->chatConversationService->processConversation($chatRequest);

        if ($chatResponse->dataCompleted) {
            $this->storeFixtureProcessor->processJsonAndSave(
                $chatResponse->fixtures,
                $chatResponse->storeConfiguration->industry,
            );
        }

        return new JsonResponse(
            [
                'conversationId' => $chatResponse->conversationId,
                'dataCompleted' => $chatResponse->dataCompleted,
                'storeInfo' => $chatResponse->storeInfo,
                'fixtures' => $chatResponse->fixtures,
                'messages' => $chatResponse->messages,
            ]
        );
    }
}
