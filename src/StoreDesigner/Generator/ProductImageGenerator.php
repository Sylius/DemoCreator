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

use App\StoreDesigner\Service\GptClient;

final readonly class ProductImageGenerator implements ImageGeneratorInterface
{
    public function __construct(private GptClient $gptClient)
    {
    }

    public function generate(string $prompt): string
    {
        return $this->gptClient->generateImage($prompt);
    }
}
