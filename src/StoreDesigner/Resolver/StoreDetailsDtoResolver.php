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

#[AsTargetedValueResolver]
readonly class StoreDetailsDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private FileResourceLoader $fileResourceLoader,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield StoreDetailsDto::fromArray(
            $request->toArray(),
            $this->fileResourceLoader->loadSchemaObject(SchemaPath::StoreDetails),
        );
    }
}
