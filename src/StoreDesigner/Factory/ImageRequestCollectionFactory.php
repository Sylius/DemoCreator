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

namespace App\StoreDesigner\Factory;

use App\StoreDesigner\Dto\AssetImageRequestDto;
use App\StoreDesigner\Dto\CustomImageRequestDto;
use App\StoreDesigner\Dto\ProductImageRequestDto;
use App\StoreDesigner\Exception\InvalidStoreDefinitionException;
use App\StoreDesigner\Util\ImageBackground;
use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;
use App\StoreDesigner\Util\StoreSection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImageRequestCollectionFactory implements ImageRequestCollectionFactoryInterface
{
    public function __construct(
        #[Autowire(env: 'WIZARD_IMAGE_QUALITY')] private string $imageQuality,
    ) {
    }

    private const MODEL = 'gpt-image-1';

    public function createFromStoreDefinition(array $storeDefinition): array
    {
        $requests = [];

        $products = $storeDefinition['products'] ?? [];
        if (!is_array($products) || empty($products)) {
            throw new InvalidStoreDefinitionException('Store definition must contain a non-empty "products" array.');
        }

        foreach ($products as $product) {
            $requests[] = new ProductImageRequestDto(
                filename: $product['images'][0],
                prompt: $product['imgPrompt'],
                model: self::MODEL,
                imageResolution: ImageResolution::Square,
                imageQuality: ImageQuality::from($this->imageQuality),
                n: 1,
            );
        }

        if (!isset($storeDefinition['themes']) || !is_array($storeDefinition['themes'])) {
            throw new InvalidStoreDefinitionException('Store definition must contain a "themes" array.');
        }

        foreach ($storeDefinition['themes'] as $section => $theme) {
            foreach ($theme['assets'] as $asset) {
                $requests[] = new AssetImageRequestDto(
                    filename: $asset['key'],
                    storeSection: StoreSection::from($section),
                    prompt: $asset['prompt'],
                    model: self::MODEL,
                    imageResolution: ImageResolution::Landscape,
                    imageQuality: ImageQuality::from($this->imageQuality),
                    imageBackground: $asset['key'] === 'logo' ? ImageBackground::Transparent : ImageBackground::Opaque,
                    n: 1,
                );
            }
        }

        return $requests;
    }

    public function createFromPromptArray(array $prompts): array
    {
        $requests = [];

        foreach ($prompts as $prompt) {
            if (!isset($prompt['name'], $prompt['imgPrompt'], $prompt['images']) || !is_array($prompt['images'])) {
                throw new InvalidStoreDefinitionException('Each prompt must contain "name", "imgPrompt", and "images" keys.');
            }

            $requests[] = new CustomImageRequestDto(
                filename: $prompt['images'][0],
                prompt: $prompt['imgPrompt'],
                model: self::MODEL,
                imageResolution: ImageResolution::Square,
                imageQuality: ImageQuality::from($this->imageQuality),
                imageBackground: ImageBackground::Opaque,
                n: 1,
            );
        }

        return $requests;
    }
}
