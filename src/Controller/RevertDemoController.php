<?php

namespace App\Controller;

use App\Service\DemoDeployer\PlatformShDeployer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/revert-demo', name: 'app_revert_demo', methods: ['POST'])]
final class RevertDemoController extends AbstractController
{
    public function __construct(private readonly PlatformShDeployer $deployer)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $environment = $data['environment'] ?? null;

        if ($environment === null) {
            return $this->json(['status' => 'error', 'message' => 'Nieprawidłowe środowisko'], Response::HTTP_BAD_REQUEST);
        }

        $this->deployer->revertDemo($environment);

        return $this->json(['status' => 'success', 'message' => 'Demo zostało przywrócone do stanu początkowego'], Response::HTTP_OK);
    }
}
