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
use App\StoreDesigner\Dto\ProductImageRequestDto;
use App\StoreDesigner\Util\ImageBackground;
use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;
use App\StoreDesigner\Util\StoreSection;

final class ImageRequestCollectionFactory implements ImageRequestCollectionFactoryInterface
{
    private const MODEL = 'gpt-image-1';

    public function createFromStoreDefinition(array $storeDefinition): array
    {
        $requests = [];

        foreach ($storeDefinition['products'] as $product) {
            $requests[] = new ProductImageRequestDto(
                filename: $product['images'][0],
                prompt: $product['imgPrompt'],
                model: self::MODEL,
                imageResolution: ImageResolution::Landscape,
                imageQuality: ImageQuality::Low,
                n: 1,
            );
        }

        foreach ($storeDefinition['themes'] as $section => $theme) {
            foreach ($theme['assets'] as $asset) {
                $requests[] = new AssetImageRequestDto(
                    filename: $asset['key'],
                    storeSection: StoreSection::from($section),
                    prompt: $asset['prompt'],
                    model: self::MODEL,
                    imageResolution: ImageResolution::Landscape,
                    imageQuality: ImageQuality::Low,
                    imageBackground: $asset['key'] === 'logo' ? ImageBackground::Transparent : ImageBackground::Opaque,
                    n: 1,
                );
            }
        }

        return $requests;
    }
}
