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

namespace App\StoreDesigner\Generator;

use App\StoreDesigner\Dto\ImageRequestInterface;
use App\StoreDesigner\Dto\ImageResponseDto;

interface ImageGeneratorInterface
{
    /**
     * @param ImageRequestInterface[] $imageRequests An array of ImageRequestDto objects.
     * @return ImageResponseDto[]
     */
    public function generateAll(array $imageRequests): array;
}
