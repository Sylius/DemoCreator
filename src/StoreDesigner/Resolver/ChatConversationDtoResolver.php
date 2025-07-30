<?php

namespace App\StoreDesigner\Resolver;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Util\FileResourceLoader;
use App\StoreDesigner\Util\SchemaPath;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver(name: self::class)]
readonly class ChatConversationDtoResolver implements ValueResolverInterface
{
    public function __construct(private FileResourceLoader $fileResourceLoader)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        yield new ChatConversationDto(
            conversationId: $data['conversationId'] ?? bin2hex(random_bytes(16)),
            messages: $data['messages'] ?? [],
            storeDetails: $data['storeDetails'] !== null ? StoreDetailsDto::fromArray($data['storeDetails'], $this->fileResourceLoader->loadSchemaObject(SchemaPath::StoreDetails)): null,
            state: isset($data['state']) ? ChatConversationState::from($data['state']) : ChatConversationState::Collecting,
            error: $data['error'] ?? null,
        );
    }
}
