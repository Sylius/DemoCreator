<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use App\StoreDesigner\Dto\ManifestDto;
use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Repository\StorePresetRepository;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Uid\Uuid;

final readonly class StorePresetManager
{
    public function __construct(
        private StorePresetRepository $storePresetRepository,
        private GptClient $gptClient,
    )
    {
    }

    public function create(): string
    {
        $timestamp = (new \DateTimeImmutable())->format('Ymd_His');
        $uuid = Uuid::v4()->toRfc4122();
        $storePresetId = "{$timestamp}_$uuid";
        $this->storePresetRepository->initializeStorePreset($storePresetId);

        return $storePresetId;
    }

    public function saveStoreDefinition(string $storePresetId, array $data): void
    {
        $this->storePresetRepository->saveStoreDefinition(
            $storePresetId,
            $data
        );
    }

    public function saveStoreDetails(string $id, StoreDetailsDto $storeDetailsDto): void
    {
        $this->storePresetRepository->saveStoreDetails($id, $storeDetailsDto);
    }

    public function updatePlugins(string $id, array $plugins): void
    {
        $this->storePresetRepository->saveManifest($id, $plugins);
    }

    public function saveFixtures(string $id, array $fixtures): void
    {
        $this->storePresetRepository->saveFixtures($id, $fixtures);
    }

    public function generateProductImages(string $storePresetId): array
    {
        $storeDefinition = $this->storePresetRepository->getStoreDefinition($storePresetId);
        $products = $storeDefinition['products'] ?? [];
        $imagesDir = $this->storePresetRepository->getStoreImagesPath($storePresetId);
        $results = ['images' => [], 'errors' => []];
        foreach ($products as $product) {
            $imgPrompt = $product['img_prompt'] ?? null;
            $images = $product['images'] ?? [];
            if (!$imgPrompt || empty($images)) continue;
            foreach ($images as $imgName) {
                $fileName = $imgName . '.png';
                $filePath = Path::join($imagesDir, $fileName);
                try {
                    $imageData = $this->gptClient->generateImage($imgPrompt);
                    if (file_put_contents($filePath, $imageData) === false) {
                        throw new \RuntimeException('Failed to save image to ' . $filePath);
                    }
                    $results['images'][] = $filePath;
                } catch (\Throwable $e) {
                    $results['errors'][] = [
                        'product' => $product['name'] ?? '',
                        'img' => $imgName,
                        'error' => $e->getMessage()
                    ];
                }
            }
        }
        return $results;
    }
}
