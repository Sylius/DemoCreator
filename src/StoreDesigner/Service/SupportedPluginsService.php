<?php

declare(strict_types=1);

namespace App\StoreDesigner\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Psr\Log\LoggerInterface;

class SupportedPluginsService
{
    private const CACHE_KEY = 'supported_plugins';
    private const CACHE_TTL = 1800; // 30 minutes in seconds

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly ?LoggerInterface $logger = null
    ) {}

    public function getSupportedPlugins(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (CacheItem $item) {
            $item->expiresAfter(self::CACHE_TTL);
            try {
                return $this->fetchFromGitHub();
            } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface | \Exception $e) {
                if ($this->logger) {
                    $this->logger->error('Failed to fetch plugins from GitHub: ' . $e->getMessage());
                }
                return $this->getStaticPlugins();
            }
        });
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
        return [
            'plugins' => [
                [
                    'name' => 'sylius/refund-plugin',
                    'versions' => ['1.0', '1.1', '1.2', '1.3']
                ],
                [
                    'name' => 'sylius/inventory-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/customer-reorder-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/plus',
                    'versions' => ['1.0', '1.1', '1.2', '1.3']
                ],
                [
                    'name' => 'sylius/admin-order-creation-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/attribute-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/availability-notifier-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/calendar-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/customer-order-cancellation-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/exchange-rate-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/order-export-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/payment-sg-checkout-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/product-reviews-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/shipping-export-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/shop-api-plugin',
                    'versions' => ['1.0', '1.1', '1.2', '1.3', '1.4']
                ],
                [
                    'name' => 'sylius/taxonomy-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/ups-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/warehouse-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/wishlist-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/notification-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/multi-source-inventory-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/elasticsearch-plugin',
                    'versions' => ['1.0', '1.1', '1.2']
                ],
                [
                    'name' => 'sylius/analytics-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/abandoned-cart-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/return-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/stock-alerts-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-inventory-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-order-management-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-pricing-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-shipping-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-tax-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-user-management-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-catalog-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-customer-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-payment-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-fulfillment-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-reporting-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-marketing-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-seo-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-security-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-performance-plugin',
                    'versions' => ['1.0', '1.1']
                ],
                [
                    'name' => 'sylius/advanced-integration-plugin',
                    'versions' => ['1.0', '1.1']
                ]
            ]
        ];
    }
} 