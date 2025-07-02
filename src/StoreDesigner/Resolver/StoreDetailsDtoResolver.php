<?php

namespace App\StoreDesigner\Resolver;

use App\StoreDesigner\Dto\ChatConversationDto;
use App\StoreDesigner\Dto\ChatConversationState;
use App\StoreDesigner\Dto\StoreDetailsDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AsTargetedValueResolver]
class StoreDetailsDtoResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $content = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $data = $content['storeDetails'] ?? [];
        
        // Jeśli storeDetails jest null lub puste, zwróć null zamiast próbować tworzyć DTO
        if (empty($data) || $data === null) {
            yield null;
            return;
        }

        yield new StoreDetailsDto(
            industry: $data['industry'] ?? 'general',
            locales: $data['locales'] ?? [],
            currencies: $data['currencies'] ?? [],
            countries: $data['countries'] ?? [],
            categories: $data['categories'] ?? [],
            productsPerCat: $data['productsPerCat'] ?? 5,
            descriptionStyle: $data['descriptionStyle'] ?? 'professional',
            imageStyle: $data['imageStyle'] ?? 'realistic',
            zones: $data['zones'] ?? [],
        );
    }
}