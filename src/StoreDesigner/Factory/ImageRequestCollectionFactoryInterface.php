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


use App\StoreDesigner\Dto\ImageRequestInterface;

interface ImageRequestCollectionFactoryInterface
{
    /** @return ImageRequestInterface[] An array of ImageRequestDto objects. */
    public function createFromStoreDefinition(array $storeDefinition): array;

    /**
     * Creates an array of ImageRequestDto objects from an array of objects that contain image prompts.
     *
     * @param array{
     *     name: string,
     *     imgPrompt: string,
     *     images: string[],
     * } $prompts An array of prompts to create image requests from.
     * @return ImageRequestInterface[] An array of ImageRequestDto objects.
     */
    public function createFromPromptArray(array $prompts): array;
}
