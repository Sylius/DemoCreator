<?php

namespace App\StoreDesigner\Resolver;

use App\StoreDesigner\Dto\ChatRequestDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ChatRequestDtoResolver implements ValueResolverInterface
{
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === ChatRequestDto::class;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        yield new ChatRequestDto(
            message: $data['message'],
            history: $data['history'] ?? [],
            storeConfiguration: isset($data['storeInfo']) ? new \App\StoreDesigner\Dto\StoreConfigurationDto(
                industry: $data['storeInfo']['industry'],
                locale: $data['storeInfo']['locale'] ?? null,
                currency: $data['storeInfo']['currency'] ?? null,
                theme: $data['storeInfo']['theme'] ?? null
            ) : null,
            fixtures: $data['fixtures'] ?? null
        );
    }
}