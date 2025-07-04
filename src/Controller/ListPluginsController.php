<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Provider\DemoConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/plugins', name: 'demo_plugins', methods: ['GET'])]
final class ListPluginsController extends AbstractController
{
    public function __construct(private readonly DemoConfigProvider $config) {}

    public function __invoke(): JsonResponse
    {
        return $this->json(['plugins' => $this->config->getPlugins()]);
    }
}
