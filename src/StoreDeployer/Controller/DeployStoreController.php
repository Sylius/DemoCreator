<?php

declare(strict_types=1);

namespace App\StoreDeployer\Controller;

use App\StoreDeployer\Deployer\StoreDeployerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DeployStoreController extends AbstractController
{
    public function __construct(
        private readonly StoreDeployerInterface $storeDeployer,
    )
    {
    }

    #[Route('/api/store-presets/{storePresetId}/deploy-store', name: 'app_deploy_store', methods: ['POST'])]
    public function deployDemo(string $storePresetId): JsonResponse
    {
        set_time_limit(1800);
        ini_set('max_execution_time', '1800');

        $this->storeDeployer->deploy($storePresetId);

        return $this->json(data: [], status: Response::HTTP_ACCEPTED);
    }
}
