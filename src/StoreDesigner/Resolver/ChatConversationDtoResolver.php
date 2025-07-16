<?php

namespace App\StoreDesigner\Resolver;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver(name: self::class)]
class ChatConversationDtoResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        yield new ChatConversationDto(
            conversationId: $data['conversationId'] ?? bin2hex(random_bytes(16)),
            messages: $data['messages'] ?? [],
            storeDetails: isset($data['storeDetails']) ? new StoreDetailsDto(
                industry: $data['storeDetails']['industry'] ?? '',
                locales: $data['storeDetails']['locales'] ?? [],
                currencies: $data['storeDetails']['currencies'] ?? [],
                countries: $data['storeDetails']['countries'] ?? [],
                categories: $data['storeDetails']['categories'] ?? [],
                productsPerCat: $data['storeDetails']['productsPerCat'] ?? 1,
                descriptionStyle: $data['storeDetails']['descriptionStyle'] ?? null,
                imageStyle: $data['storeDetails']['imageStyle'] ?? null,
                zones: $data['storeDetails']['zones'] ?? [],
            ) : null,
            state: isset($data['state']) ? ChatConversationState::from($data['state']) : ChatConversationState::Collecting,
            error: $data['error'] ?? null,
        );
    }
}