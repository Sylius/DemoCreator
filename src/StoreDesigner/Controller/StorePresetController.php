<?php
namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Resolver\ChatConversationDtoResolver;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\FixtureCreator;
use App\StoreDesigner\Service\FixtureParser;
use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;

class StorePresetController extends AbstractController
{
    public function __construct(
        private readonly StorePresetManager $storePresetManager,
        private readonly FixtureCreator $fixtureCreator,
        private readonly FixtureParser $fixtureParser,
    ) {}

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

    #[Route('/api/store-presets/{id}/fixtures-generate', name: 'generate_store_preset_fixtures', methods: ['PATCH'])]
    public function generateFixtures(
        string $id,
        #[ValueResolver(StoreDetailsDtoResolver::class)] StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $preset = $this->storePresetManager->getPreset($id);
        if (!$preset) {
            return $this->json(['error' => 'Preset not found'], 404);
        }

        try {
            $storeDefinition = $this->fixtureCreator->create($storeDetailsDto);
            $fixtures = $this->fixtureParser->parse($storeDefinition);
            $this->storePresetManager->updateStoreDefinition(array_merge($storeDefinition, ['id' => $id]));
            $this->storePresetManager->updateFixtures($id, $fixtures);
            $response = $this->json([
                'message' => 'Fixtures generated',
                'fixturesCount' => count($fixtures),
                'presetId' => $id
            ], 200);
            $response->headers->set('X-Debug-Max-Execution-Time', ini_get('max_execution_time'));
            return $response;
        } catch (\Throwable $e) {
            $response = $this->json(['error' => 'Failed to generate fixtures: ' . $e->getMessage()], 500);
            $response->headers->set('X-Debug-Max-Execution-Time', ini_get('max_execution_time'));
            return $response;
        }
    }

    #[Route('/api/store-presets/{id}/fixtures-parse', name: 'update_store_preset_definition', methods: ['PATCH'])]
    public function updateDefinition(string $id, Request $request): JsonResponse
    {
        $preset = $this->storePresetManager->getPreset($id);
        if (!$preset) {
            return $this->json(['error' => 'Preset not found'], 404);
        }

        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(['error' => 'No definition provided'], 400);
        }

        try {
            $data['id'] = $id;
            $this->storePresetManager->updateStoreDefinition($data);
            $fixtures = $this->fixtureParser->parse($data);
            $this->storePresetManager->updateFixtures($id, $fixtures);
            return $this->json([
                'message' => 'Definition and fixtures updated successfully',
                'fixturesCount' => count($fixtures),
                'id' => $id
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to update definition: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/api/store-presets/{id}/generate-images', name: 'generate_store_preset_images', methods: ['PATCH'])]
    public function generateImages(string $id): JsonResponse
    {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $preset = $this->storePresetManager->getPreset($id);
        if (!$preset) {
            return $this->json(['error' => 'Preset not found'], 404);
        }
        try {
            $result = $this->storePresetManager->generateProductImages($id);
            return $this->json([
                'message' => 'Images generated',
                'count' => count($result['images'] ?? []),
                'images' => $result['images'] ?? [],
                'errors' => $result['errors'] ?? [],
                'presetId' => $id
            ], 200);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Failed to generate images: ' . $e->getMessage()], 500);
        }
    }
} 