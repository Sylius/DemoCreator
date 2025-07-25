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

use App\StoreDesigner\Dto\ProductImageRequestDto;
use App\StoreDesigner\Exception\InvalidStoreDefinitionException;
use App\StoreDesigner\Filesystem\ImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
use App\StoreDesigner\Message\GenerateProductImagesMessage;
use App\StoreDesigner\Util\ImageQuality;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateProductImagesMessageHandler
{


    public function __invoke(GenerateProductImagesMessage $message): void
    {

    }
}
