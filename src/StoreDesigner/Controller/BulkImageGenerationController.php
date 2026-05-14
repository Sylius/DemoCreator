<?php

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Dto\StoreDetailsDto;
use App\StoreDesigner\Factory\ImageRequestCollectionFactoryInterface;
use App\StoreDesigner\Factory\StorePresetFactory;
use App\StoreDesigner\Filesystem\ImagePersisterInterface;
use App\StoreDesigner\Filesystem\StoreDefinitionReader;
use App\StoreDesigner\Filesystem\StoreFilesystemPersister;
use App\StoreDesigner\Generator\ImageGeneratorInterface;
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

/** Controller for handling bulk image generation requests unrelated to the Store Wizard flow.*/
class BulkImageGenerationController extends AbstractController
{
    public function __construct(
        private readonly ImageRequestCollectionFactoryInterface $imageRequestCollectionFactory,
        private readonly ImageGeneratorInterface $imageGenerator,
        private readonly ImagePersisterInterface $imagePersister,
    ) {
    }

    #[Route('/api/generate-bulk-images/{storePresetId}', name: 'generate_bulk_images', methods: ['POST'])]
    public function create(
        string $storePresetId,
        Request $request,
    ): JsonResponse {
        set_time_limit(3600);
        ini_set('max_execution_time', '3600');

        $prompts = json_decode($request->getContent(), true);
        if (!is_array($prompts) || empty($prompts)) {
            return $this->json([
                'status' => 'error',
                'message' => 'Invalid or empty prompts provided.',
            ], 400);
        }

        foreach ($prompts as &$prompt) {
            if (isset($prompt['name'])) {
                $snake = strtolower($prompt['name']);
                $snake = preg_replace('/[\s\-]+/', '_', $snake);
                $snake = preg_replace('/[^a-z0-9_]/', '', $snake);
                $snake = trim($snake, '_');
                $prompt['images'][0] = $snake;
            }
        }
        unset($prompt);

        $imageRequests = $this->imageRequestCollectionFactory->createFromPromptArray($prompts);
        $results = $this->imageGenerator->generateAll($imageRequests);
        $this->imagePersister->persistAll($storePresetId, $results);

        return $this->json([
            'status' => 'success',
            'message' => 'Bulk image generation completed successfully.',
        ]);
    }
}
