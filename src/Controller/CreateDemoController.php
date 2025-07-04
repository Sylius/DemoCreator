<?php

declare(strict_types=1);

namespace App\Controller;

use App\Constraint\CreateDemoConstraint;
use App\Exception\InvalidStorePresetException;
use App\Service\DemoDeployer\PlatformShDeployer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/create-demo', name: 'demo_create', methods: ['POST'])]
final class CreateDemoController extends AbstractController
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly PlatformShDeployer $deployer,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $violations = $this->validator->validate(new CreateDemoConstraint($payload));
        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->deployer->deploy(store: $payload['store'], environment: $payload['environment']);
        } catch (InvalidStorePresetException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Throwable $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json(['activityId' => $result->activityId, 'url' => $result->url], Response::HTTP_OK);
    }
}
