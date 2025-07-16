<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\Exception\InvalidStorePresetException;
use App\Service\DemoDeployer\PlatformShDeployer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/store-presets/{id}/create-demo', name: 'store_presets_demo_create', methods: ['POST'])]
final class CreateDemoController extends AbstractController
{
    public function __construct(
        private readonly PlatformShDeployer $deployer,
    ) {
    }

    public function __invoke(string $id): JsonResponse
    {
        set_time_limit(600);
        ini_set('max_execution_time', '600');

//        $violations = $this->validator->validate(new CreateDemoConstraint($payload));
//        if ($violations->count() > 0) {
//            $errors = [];
//            foreach ($violations as $violation) {
//                $errors[$violation->getPropertyPath()] = $violation->getMessage();
//            }
//            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
//        }

        try {
            $result = $this->deployer->deploy(store: $id, environment: 'main');
        } catch (InvalidStorePresetException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['activityId' => $result->activityId, 'url' => $result->url], Response::HTTP_OK);
    }
}
