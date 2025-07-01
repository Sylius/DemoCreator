<?php
namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class StorePresetController extends AbstractController
{
    public function __construct(private readonly StorePresetManager $storePresetManager) {}

    #[Route('/api/store-presets', name: 'create_store_preset', methods: ['POST'])]
    public function createPreset(): JsonResponse
    {
        return $this->json(['storePresetId' => $this->storePresetManager->create()]);
    }

    #[Route('/api/store-presets/{presetId}', name: 'update_store_preset', methods: ['PATCH'])]
    public function updatePreset(string $presetId, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->storePresetManager->updatePreset($presetId, $data);
        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/store-presets/{presetId}', name: 'get_store_preset', methods: ['GET'])]
    public function getPreset(string $presetId): JsonResponse
    {
        $preset = $this->storePresetManager->getPreset($presetId);
        if (!$preset) {
            return $this->json(['error' => 'Preset not found'], 404);
        }
        return $this->json($preset);
    }

    #[Route('/api/store-presets/{presetId}', name: 'delete_store_preset', methods: ['DELETE'])]
    public function deletePreset(string $presetId): JsonResponse
    {
        $this->storePresetManager->deletePreset($presetId);
        return $this->json(['status' => 'deleted']);
    }
} 