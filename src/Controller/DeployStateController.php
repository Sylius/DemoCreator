<?php

namespace App\Controller;

use App\Service\DemoDeployer\PlatformShDeployer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DeployStateController extends AbstractController
{
    public function __construct(private readonly PlatformShDeployer $deployer)
    {
    }

    #[Route('/deploy-state/{environment}/{activityId}', name: 'demo_deploy_state', methods: ['GET'])]
    public function index(string $environment, string $activityId): JsonResponse
    {
        $deployState = $this->deployer->getDeployState($environment, $activityId);

        return $this->json($deployState, Response::HTTP_OK);
    }
}
