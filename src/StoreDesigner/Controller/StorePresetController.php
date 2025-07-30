<?php

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Factory\StorePresetFactory;
use App\StoreDesigner\Filesystem\StoreFilesystemPersister;
use App\StoreDesigner\Message\GenerateBannerImageMessage;
use App\StoreDesigner\Message\GenerateLogoImageMessage;
use App\StoreDesigner\Message\GenerateProductImagesMessage;
use App\StoreDesigner\Orchestrator\StoreGenerationOrchestrator;
use App\StoreDesigner\Parser\FixtureParser;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Annotation\Route;

class StorePresetController extends AbstractController
{
    public function __construct(
        private readonly StorePresetManager $storePresetManager,
        private readonly StoreGenerationOrchestrator $storeGenerationOrchestrator,
        private readonly StorePresetFactory $storePresetFactory,
        #[Autowire(env: 'STORE_DEPLOY_TARGET')] private readonly string $deployTarget,
    ) {
    }

    #[Route('/api/store-presets', name: 'create_store_preset', methods: ['POST'])]
    public function createPreset(): JsonResponse
    {
        return $this->json([
            'storePresetId' => $this->storePresetFactory->create(),
            'deployTarget' => $this->deployTarget,
            ]);
    }

    #[Route('/api/store-presets/{presetId}', name: 'update_store_preset', methods: ['PATCH'])]
    public function updatePlugins(string $presetId, Request $request): JsonResponse
    {
        $this->storePresetManager->updatePlugins($presetId, json_decode($request->getContent(), true)['plugins']);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/store-presets/{storePresetId}/generate-store', name: 'generate_store', methods: ['POST'])]
    public function generateStore(
        string $storePresetId,
        #[ValueResolver(StoreDetailsDtoResolver::class)] StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        set_time_limit(1800);
        ini_set('max_execution_time', '1800');

        $this->storeGenerationOrchestrator->orchestrate($storePresetId, $storeDetailsDto);

        $response = $this->json([]);
        $response->headers->set('X-Debug-Max-Execution-Time', ini_get('max_execution_time'));

        return $response;
    }

    #[Route('/api/store-presets/{presetId}/parse-fixtures', name: 'parse_fixtures', methods: ['POST'])]
    public function parseFixtures(
        string $presetId,
        Request $request,
        FixtureParser $fixtureParser,
        StoreFilesystemPersister $storeFilesystemPersister,
    ): JsonResponse {
        $fixtures = $fixtureParser->parse($request->toArray() ?? []);
        $storeFilesystemPersister->saveFixtures($presetId, $fixtures);

        return $this->json(['status' => 'ok']);
    }
}
