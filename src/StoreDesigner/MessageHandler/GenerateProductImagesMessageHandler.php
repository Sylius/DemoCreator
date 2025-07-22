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

use App\StoreDesigner\Exception\InvalidStoreDefinitionException;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Generator\ProductImageGenerator;
use App\StoreDesigner\Message\GenerateProductImagesMessage;
use App\StoreDesigner\Persister\ImagePersisterInterface;

final readonly class GenerateProductImagesMessageHandler
{
    public function __construct(
        private StoreDefinitionReader $storeDefinitionReader,
        private ProductImageGenerator $imageGenerator,
        private ImagePersisterInterface $persister,
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
            $prompt = $product['img_prompt'] ?? null;
            foreach ($product['images'] ?? [] as $name) {
                $binary = $this->imageGenerator->generate($prompt);
                $this->persister->saveGeneratedImage($message->storePresetId, $name, $binary);
            }
        }
    }
}
