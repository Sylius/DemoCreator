<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Resolver\StoreDetailsDtoResolver;
use App\StoreDesigner\Service\FixtureCreator;
use App\StoreDesigner\Service\FixtureParser;
use App\StoreDesigner\Service\StorePresetManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

final class CreateFixturesController extends AbstractController
{
    public function __construct(
        private readonly FixtureCreator $fixtureCreator,
        private readonly FixtureParser $fixtureParser,
        private readonly StorePresetManager $storePresetManager,
    ) {
    }

    #[Route('/api/create-fixtures', name: 'api_create_fixtures', methods: ['POST'])]
    public function createFixtures(
        #[ValueResolver(StoreDetailsDtoResolver::class)]
        ?StoreDetailsDto $storeDetailsDto,
    ): JsonResponse {
        // Increase execution time limit for GPT generation
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', '300');
        
        if (!$storeDetailsDto) {
            return $this->json(
                data: ['error' => 'Store details are required. Please complete the store description first.'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $storeDefinition = $this->fixtureCreator->create($storeDetailsDto);
            $this->storePresetManager->saveRawAssistantResponse($storeDefinition);
            $fixtures = $this->fixtureParser->parse($storeDefinition);

            $this->storePresetManager->updateStoreDefinition($storeDefinition);
            $this->storePresetManager->updateFixtures(
                $storeDefinition['storePresetName'],
                $fixtures,
            );

            return $this->json(
                data: ['message' => 'Fixtures created successfully'],
                status: Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return $this->json(
                data: ['error' => 'Failed to create fixtures: ' . $e->getMessage()],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/api/parse-fixtures', name: 'api_parse_fixtures', methods: ['POST'])]
    public function parseFixtures(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (empty($data)) {
            return $this->json(
                data: ['error' => 'No fixtures provided'],
                status: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $this->storePresetManager->updateStoreDefinition($data);
            $this->storePresetManager->updateFixtures($data['storePresetName'], $this->fixtureParser->parse($data));

            return $this->json(
                data: ['message' => 'Fixtures parsed and updated successfully'],
                status: Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return $this->json(
                data: ['error' => 'Failed to parse fixtures: ' . $e->getMessage()],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/api/create-fixtures-progress/{presetId}', name: 'api_create_fixtures_progress', methods: ['GET'])]
    public function createFixturesProgress(string $presetId): Response
    {
        // Set headers for Server-Sent Events
        $response = new Response();
        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', 'Cache-Control');
        
        // Send initial progress
        $response->setContent("data: " . json_encode(['status' => 'starting', 'message' => 'Initializing GPT generation...']) . "\n\n");
        
        return $response;
    }
}
