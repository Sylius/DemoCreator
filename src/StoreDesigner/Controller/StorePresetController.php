<?php

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Factory\StorePresetFactory;
use App\StoreDesigner\Message\GenerateProductImagesMessage;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\StoreGenerationOrchestrator;
use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class StorePresetController extends AbstractController
{
    public function __construct(
        private readonly StorePresetManager $storePresetManager,
        private readonly StoreGenerationOrchestrator $storeGenerationOrchestrator,
        private readonly StorePresetFactory $storePresetFactory,
    ) {
    }

    #[Route('/api/store-presets', name: 'create_store_preset', methods: ['POST'])]
    public function createPreset(): JsonResponse
    {
        return $this->json(['storePresetId' => $this->storePresetFactory->create()]);
    }

    #[Route('/api/store-presets/{presetId}', name: 'update_store_preset', methods: ['PATCH'])]
    public function updatePlugins(string $presetId, Request $request): JsonResponse
    {
        $this->storePresetManager->updatePlugins($presetId, json_decode($request->getContent(), true));

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/store-presets/{storePresetId}/generate-store', name: 'generate_store', methods: ['POST'])]
    public function generateStore(
        string $storePresetId,
        #[ValueResolver(StoreDetailsDtoResolver::class)] StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $this->storeGenerationOrchestrator->orchestrate($storePresetId, $storeDetailsDto);

        $response = $this->json([]);
        $response->headers->set('X-Debug-Max-Execution-Time', ini_get('max_execution_time'));

        return $response;
    }

    #[Route('/api/store-presets/{id}/generate-images', name: 'generate_store_preset_images', methods: ['PATCH'])]
    public function generateImages(string $id, MessageBusInterface $messageBus): JsonResponse
    {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $messageBus->dispatch(new GenerateProductImagesMessage($id));

        return $this->json([
            'status' => 'accepted',
            'presetId' => $id,
        ], 202);
    }

    #[Route('/api/store-presets/{id}/generate-banner', name: 'generate_store_preset_banner', methods: ['PATCH'])]
    public function generateBanner(string $id, Request $request): JsonResponse
    {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $path = $this->storePresetManager->generateBannerImage($id, $request->get('prompt', ''));

        return $this->json([
            'message' => 'Banner generated',
            'path' => $path,
            'presetId' => $id
        ]);
    }
}
