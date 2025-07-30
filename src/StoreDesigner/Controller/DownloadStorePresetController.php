<?php
namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DownloadStorePresetController extends AbstractController
{
    public function __construct(private readonly StorePresetManager $storePresetManager)
    {
    }

    #[Route('/api/download-store-preset/{presetName}', name: 'download_store_preset', methods: ['GET'])]
    public function downloadStorePreset(string $presetName): Response
    {
        try {
            $tmpZip = $this->storePresetManager->zipStorePreset($presetName);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
        $zipName = $presetName . '.zip';

        return $this->file($tmpZip, $zipName)->deleteFileAfterSend();
    }
}
