<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Resolver\ChatConversationDtoResolver;
use App\StoreDesigner\Service\ChatConversationService;
use App\StoreDesigner\Service\FixtureCreator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class ChatController extends AbstractController
{
    public function __construct(private readonly ChatConversationService $chatConversationService)
    {
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(
        #[ValueResolver(ChatConversationDtoResolver::class)]
        ChatConversationDto $chatConversationDto,
    ): JsonResponse {
        return $this->json($this->chatConversationService->processConversation($chatConversationDto));
    }
}
