<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Provider\DemoConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/environments', name: 'demo_environments', methods: ['GET'])]
final class ListEnvironmentsController extends AbstractController
{
    public function __construct(private readonly DemoConfigProvider $config) {}

    public function __invoke(): JsonResponse
    {
        return $this->json(['environments' => $this->config->getEnvironments()]);
    }
}
