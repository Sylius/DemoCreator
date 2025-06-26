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
class StoreDetailsDtoResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['storeDetails'] ?? [];

        yield new StoreDetailsDto(
            industry: $data['industry'],
            locales: $data['locales'],
            currencies: $data['currencies'],
            countries: $data['countries'],
            categories: $data['categories'],
            productsPerCat: $data['productsPerCat'],
            descriptionStyle: $data['descriptionStyle'],
            imageStyle: $data['imageStyle'],
            zones: $data['zones'],
        );
    }
}