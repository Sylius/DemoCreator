<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\StoreDesigner\MessageHandler;

use App\StoreDesigner\Dto\ImageRequestDto;
use App\StoreDesigner\Exception\InvalidStoreDefinitionException;
use App\StoreDesigner\Filesystem\ProductImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
use App\StoreDesigner\Message\GenerateProductImagesMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateProductImagesMessageHandler
{
    public function __construct(
        private StoreDefinitionReader $storeDefinitionReader,
        private ImageGeneratorInterface $imageGenerator,
        private ProductImagePersisterInterface $imagePersister,
    ) {
    }

    public function __invoke(GenerateProductImagesMessage $message): void
    {
        $definition = $this->storeDefinitionReader->getStoreDefinition($message->storePresetId);
        $products = $definition['products'] ?? [];

        if (empty($products)) {
            throw new InvalidStoreDefinitionException(
                sprintf('No products found in store definition for preset ID "%s".', $message->storePresetId)
            );
        }

        foreach ($products as $product) {
            foreach ($product['images'] ?? [] as $imageName) {
                $binary = $this->imageGenerator->generate(new ImageRequestDto(prompt: $product['imgPrompt']));
                $this->imagePersister->persist(
                    $message->storePresetId,
                    $imageName,
                    $binary,
                );
            }
        }
    }
}
