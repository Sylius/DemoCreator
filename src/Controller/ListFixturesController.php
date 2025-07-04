<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Provider\DemoConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/fixtures', name: 'demo_fixtures', methods: ['GET'])]
final class ListFixturesController extends AbstractController
{
    public function __construct(private readonly DemoConfigProvider $config) {}

    public function __invoke(): JsonResponse
    {
        return $this->json(['fixtures' => $this->config->getFixtures()]);
    }
}
