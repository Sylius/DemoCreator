<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoDeployer\DemoDeployerInterface;
use App\Service\DemoDeployer\PlatformShDeployer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/create-demo', name: 'demo_create', methods: ['POST'])]
final class CreateDemoController extends AbstractController
{
    public function __construct(private readonly PlatformShDeployer $demoDeployer)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['environment']) || !is_string($data['environment'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing or invalid "environment"'
            ], 400);
        }
        $environment = $data['environment'];

        if (empty($data['plugins']) || !is_array($data['plugins'])) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Missing or invalid "plugins" array'
            ], 400);
        }
        $plugins = $data['plugins'];

        try {
            $result = $this->demoDeployer->deploy($environment, $plugins);

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
