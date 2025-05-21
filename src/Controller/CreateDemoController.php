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
        private readonly DemoConfigProvider $configProvider,
    )
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $env = $data['environment'] ?? '';
        if (!is_string($env) || !$env) {
            return $this->json(['status' => 'error', 'message' => 'Nieprawidłowe środowisko'], 400);
        }

        $rawPlugins = $data['plugins'] ?? [];
        if (!is_array($rawPlugins)) {
            return $this->json(['status' => 'error', 'message' => 'Brak pluginów'], 400);
        }

        $available = array_column($this->configProvider->getPlugins(), 'composer');
        // zostawiamy tylko te klucze, które są na liście allowed
        $plugins = array_intersect_key(
            $rawPlugins,
            array_flip($available)
        );

        if (count($plugins) === 0) {
            return $this->json(['status' => 'error', 'message' => 'Wybierz przynajmniej jeden plugin'], 400);
        }

        // (pomijam fixtures/target/logo dla skrótu)

        try {
            $result = $this->deployer->deploy($env, $plugins);

            return $this->json($result);
        } catch (\Throwable $e) {
            return $this->json(
                ['status' => 'error', 'message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
