<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class SupportedPluginsService
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    public function getSupportedPlugins(): array
    {
        try {
            return $this->fetchFromGitHub();
        } catch (\Throwable $e) {
            return $this->getStaticPlugins();
        }
    }

    private function fetchFromGitHub(): array
    {
        $baseUrl = 'https://api.github.com/repos/Sylius/StoreAssembler/contents/config/plugins';
        // Fetch vendor directories
        $response = $this->httpClient->request('GET', $baseUrl, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'Sylius-DemoCreator/1.0',
            ],
        ]);
        $vendors = $response->toArray();
        $plugins = [];

        foreach ($vendors as $vendorDir) {
            if ($vendorDir['type'] !== 'dir') {
                continue;
            }
            $vendorName = $vendorDir['name'];
            // Fetch plugin directories under this vendor
            $pluginsResp = $this->httpClient->request('GET', $vendorDir['url'], [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'Sylius-DemoCreator/1.0',
                ],
            ]);
            $pluginDirs = $pluginsResp->toArray();

            foreach ($pluginDirs as $pluginDir) {
                if ($pluginDir['type'] !== 'dir') {
                    continue;
                }
                $pluginName = $pluginDir['name'];
                // Fetch version directories for this plugin
                $versionsResp = $this->httpClient->request('GET', $pluginDir['url'], [
                    'headers' => [
                        'Accept' => 'application/vnd.github.v3+json',
                        'User-Agent' => 'Sylius-DemoCreator/1.0',
                    ],
                ]);
                $versionsData = $versionsResp->toArray();
                $versions = [];
                foreach ($versionsData as $ver) {
                    if ($ver['type'] === 'dir') {
                        $versions[] = $ver['name'];
                    }
                }

                $plugins[] = [
                    'name' => sprintf('%s/%s', $vendorName, $pluginName),
                    'versions' => $versions,
                ];
            }
        }

        return ['plugins' => $plugins];
    }

    private function getStaticPlugins(): array
    {
        // Static fallback based on Sylius StoreAssembler structure
        $plugins = [
            [
                'name' => 'sylius/cms-plugin',
                'versions' => ['1.0'],
            ],
            [
                'name' => 'sylius/customer-service-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/invoicing-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/loyalty-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/refund-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/return-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/rfq-plugin',
                'versions' => ['2.0'],
            ],
            [
                'name' => 'sylius/wishlist-plugin',
                'versions' => ['1.0'],
            ],
        ];

        // Ensure unique plugins by name (in case duplicates existed)
        $uniquePlugins = [];
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            if (!isset($uniquePlugins[$name])) {
                // Remove duplicate versions and sort ascending
                $versions = array_unique($plugin['versions']);
                sort($versions, SORT_STRING);
                $uniquePlugins[$name] = [
                    'name' => $name,
                    'versions' => $versions,
                ];
            } else {
                // Merge versions if duplicate plugin found
                $mergedVersions = array_unique(array_merge($uniquePlugins[$name]['versions'], $plugin['versions']));
                sort($mergedVersions, SORT_STRING);
                $uniquePlugins[$name]['versions'] = $mergedVersions;
            }
        }

        // Sort plugins alphabetically by name
        usort($uniquePlugins, static fn($a, $b) => strcmp($a['name'], $b['name']));

        return [
            'plugins' => $uniquePlugins,
        ];
    }
} 