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
use App\StoreDesigner\Filesystem\ImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
use App\StoreDesigner\Message\GenerateBannerImageMessage;
use App\StoreDesigner\Util\ImageType;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateBannerImageMessageHandler
{
    private const BANNER_NAME = 'banner';
    public function __construct(
        private StoreDefinitionReader $storeDefinitionReader,
        private ImageGeneratorInterface $imageGenerator,
        private ImagePersisterInterface $imagePersister,
    ) {
    }

    public function __invoke(GenerateBannerImageMessage $message): void
    {
        $definition = $this->storeDefinitionReader->getStoreDefinition($message->storePresetId);

        $binary = $this->imageGenerator->generate(new ImageRequestDto(
            prompt: $this->getBannerPrompt($definition),
            size: '1536x1024',
        ));

        $this->imagePersister->persistImage(
            $message->storePresetId,
            self::BANNER_NAME,
            $binary,
            ImageType::BANNER
        );
    }

    private function getBannerPrompt(array $definition): string
    {
        if (!isset($definition['theme']['assets']) || !is_array($definition['theme']['assets'])) {
            throw new InvalidStoreDefinitionException(
                sprintf('Store preset "%s" does not have a valid theme assets definition.', $definition['id'] ?? 'unknown'),
            );
        }

        foreach ($definition['theme']['assets'] as $asset) {
            if (isset($asset['key'], $asset['prompt']) && $asset['key'] === 'banner') {
                return $asset['prompt'];
            }
        }

        throw new InvalidStoreDefinitionException(
            sprintf('Store preset "%s" does not have a banner prompt defined in theme assets.', $definition['id'] ?? 'unknown'),
        );
    }
}
