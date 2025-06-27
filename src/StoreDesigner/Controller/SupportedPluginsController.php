<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SupportedPluginsController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

    #[Route('/api/supported-plugins', name: 'app_supported_plugins', methods: ['GET'])]
    public function supportedPlugins(): JsonResponse
    {
        $url = 'https://api.github.com/repos/Sylius/StoreAssembler/contents/config/plugins/sylius';
        $response = $this->httpClient->request('GET', $url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
            ]
        ]);
        $data = $response->toArray();

        $plugins = [];
        foreach ($data as $item) {
            if ($item['type'] === 'dir') {
                $pluginName = $item['name'];
                // Get versions (subdirectories)
                $versionsResp = $this->httpClient->request('GET', $item['url'], [
                    'headers' => ['Accept' => 'application/vnd.github.v3+json']
                ]);
                $versionsData = $versionsResp->toArray();
                $versions = [];
                foreach ($versionsData as $ver) {
                    if ($ver['type'] === 'dir') {
                        $versions[] = $ver['name'];
                    }
                }
                $plugins[] = [
                    'name' => $pluginName,
                    'versions' => $versions,
                ];
            }
        }

        return $this->json(['plugins' => $plugins]);
    }
}
