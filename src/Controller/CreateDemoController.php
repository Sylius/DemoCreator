<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\DemoDeployer\PlatformShDeployer;
use App\Service\Provider\DemoConfigProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/create-demo', name: 'demo_create', methods: ['POST'])]
final class CreateDemoController extends AbstractController
{
    public function __construct(
        private readonly PlatformShDeployer $deployer,
        private readonly DemoConfigProvider $configProvider
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $env = $data['environment'] ?? '';
        if (!is_string($env) || $env === '') {
            return $this->json(['status' => 'error', 'message' => 'Nieprawidłowe środowisko'], 400);
        }


        $availablePlugins = $this->configProvider->getPlugins();
        $plugins = array_filter((array)($data['plugins'] ?? []), fn($p) => in_array($p, $availablePlugins, true));
        if (empty($plugins)) {
            return $this->json(['status' => 'error', 'message' => 'Wybierz przynajmniej jeden plugin'], 400);
        }


//        $availableFixtures = $this->configProvider->getFixtures();
//        $fixtures = array_filter((array)($data['fixtures'] ?? []), fn($f) => in_array($f, $availableFixtures, true));
//        if (empty($fixtures)) {
//            return $this->json(['status' => 'error', 'message' => 'Wybierz przynajmniej jedną fixture'], 400);
//        }

        // walidacja targetu
//        $availableTargets = $this->configProvider->getDeployTargets();
//        $target = $data['target'] ?? '';
//        if (!in_array($target, $availableTargets, true)) {
//            return $this->json(['status' => 'error', 'message' => 'Nieprawidłowy target'], 400);
//        }

        // logoUrl jest opcjonalne
        $logoUrl = $data['logoUrl'] ?? null;

        try {
            $result = $this->deployer->deploy($env, $plugins);
            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json(['status' => 'error', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
