<?php

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\StoreDefinitionGenerator;
use App\StoreDesigner\Service\FixtureParser;
use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;

class StorePresetController extends AbstractController
{
    public function __construct(
        private readonly StorePresetManager $storePresetManager,
        private readonly StoreDefinitionGenerator $storeDefinitionGenerator,
        private readonly FixtureParser $fixtureParser,
    ) {
    }

    #[Route('/api/store-presets', name: 'create_store_preset', methods: ['POST'])]
    public function createPreset(): JsonResponse
    {
        return $this->json(['storePresetId' => $this->storePresetManager->create()]);
    }

    #[Route('/api/store-presets/{presetId}', name: 'update_store_preset', methods: ['PATCH'])]
    public function updatePlugins(string $presetId, Request $request): JsonResponse
    {
        $this->storePresetManager->updatePlugins($presetId, json_decode($request->getContent(), true));

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/store-presets/{id}/generate-store-definition', name: 'generate_store_definition', methods: ['POST'])]
    public function generateStoreDefinition(
        string $id,
        #[ValueResolver(StoreDetailsDtoResolver::class)] StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $storeDefinition = $this->storeDefinitionGenerator->generate($storeDetailsDto);
        $fixtures = $this->fixtureParser->parse($storeDefinition);
        $this->storePresetManager->saveStoreDefinition($id, $storeDefinition);
        $this->storePresetManager->saveStoreDetails($id, $storeDetailsDto);
        $this->storePresetManager->saveFixtures($id, $fixtures);
        $response = $this->json([
            'message' => 'Fixtures generated',
            'fixturesCount' => count($fixtures),
            'presetId' => $id
        ]);
        $response->headers->set('X-Debug-Max-Execution-Time', ini_get('max_execution_time'));
        return $response;
    }

    #[Route('/api/store-presets/{id}/generate-images', name: 'generate_store_preset_images', methods: ['PATCH'])]
    public function generateImages(string $id): JsonResponse
    {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

        $result = $this->storePresetManager->generateProductImages($id);

        return $this->json([
            'message' => 'Images generated',
            'count' => count($result['images'] ?? []),
            'images' => $result['images'] ?? [],
            'errors' => $result['errors'] ?? [],
            'presetId' => $id
        ]);
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
