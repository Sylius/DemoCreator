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
use App\StoreDesigner\Filesystem\AssetImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
use App\StoreDesigner\Message\GenerateBannerImageMessage;
use App\StoreDesigner\Util\ImageQuality;
use App\StoreDesigner\Util\ImageResolution;
use App\StoreDesigner\Util\StoreSection;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class GenerateLogoImageMessageHandler
{
    private const LOGO_NAME = 'logo';
    public function __construct(
        private StoreDefinitionReader $storeDefinitionReader,
        private ImageGeneratorInterface $imageGenerator,
        private AssetImagePersisterInterface $assetImagePersister,
    ) {
    }

    public function __invoke(GenerateBannerImageMessage $message): void
    {
        $definition = $this->storeDefinitionReader->getStoreDefinition($message->storePresetId);

        $binary = $this->imageGenerator->generate(new ImageRequestDto(
            prompt: $this->getLogoPrompt($message->storePresetId, $definition),
            imageResolution: ImageResolution::Landscape,
            imageQuality: ImageQuality::Low,
        ));

        $this->assetImagePersister->persist(
            $message->storePresetId,
            StoreSection::Shop,
            self::LOGO_NAME,
            $binary,
        );
    }

    private function getLogoPrompt(string $storePresetId, array $definition): string
    {
        $assets = $definition['themes'][StoreSection::Shop->value]['assets'] ?? [];

        if (!is_array($assets)) {
            throw new InvalidStoreDefinitionException(
                sprintf('Invalid assets for shop theme in preset "%s".', $storePresetId)
            );
        }

        $prompt = array_column($assets, 'prompt', 'key')[self::LOGO_NAME] ?? null;

        if ($prompt === null) {
            throw new InvalidStoreDefinitionException(sprintf(
                'No logo prompt found in store definition for preset ID "%s".',
                $storePresetId,
            ));
        }

        return $prompt;
    }
}
