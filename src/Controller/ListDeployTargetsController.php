<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Provider\DemoConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/deploy-targets', name: 'demo_targets', methods: ['GET'])]
final class ListDeployTargetsController extends AbstractController
{
    public function __construct(private readonly DemoConfigProvider $config) {}

    public function __invoke(): JsonResponse
    {
        return $this->json(['targets' => $this->config->getDeployTargets()]);
    }
}
