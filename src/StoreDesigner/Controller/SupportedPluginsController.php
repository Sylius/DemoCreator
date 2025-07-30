<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use App\StoreDesigner\Service\SupportedPluginsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class SupportedPluginsController extends AbstractController
{
    public function __construct(private readonly SupportedPluginsService $pluginsService) {}

    #[Route('/api/supported-plugins', name: 'app_supported_plugins', methods: ['GET'])]
    public function supportedPlugins(): JsonResponse
    {
        $data = $this->pluginsService->getSupportedPlugins();
        return $this->json($data);
    }
}
