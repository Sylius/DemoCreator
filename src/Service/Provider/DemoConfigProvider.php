<?php

declare(strict_types=1);

namespace App\Service\Provider;

final class DemoConfigProvider
{
    public function getPlugins(): array
    {
        return [
            [
                'name' => 'B2B Kit',
                'composer' => 'sylius/b2b-kit',
                'version' => '2.0.x-dev',
            ],
            [
                'name' => 'CMS Plugin',
                'composer' => 'sylius/cms-plugin',
                'version' => '1.0.x-dev'
            ],
            [
                'name' => 'Customer Service Plugin',
                'composer' => 'sylius/customer-service-plugin',
                'version' => '2.0.x-dev'
            ],
            [
                'name' => 'Loyalty Plugin',
                'composer' => 'sylius/loyalty-plugin',
                'version' => '2.0.x-dev'
            ],
            [
                'name' => 'Return Plugin',
                'composer' => 'sylius/return-plugin',
                'version' => '2.0.x-dev'
            ],
            [
                'name' => 'Invoicing Plugin',
                'composer' => 'sylius/invoicing-plugin',
                'version' => '2.0.x-dev'
            ],
        ];
    }

    public function getFixtures(): array
    {
        return [
            'tractors',
        ];
    }

    public function getLogo(): array
    {
        return [
            'tractor',
        ];
    }

    public function getDeployTargets(): array
    {
        return [
            'platform.sh',
        ];
    }

    public function getEnvironments(): array
    {
        return [
            'booster',
            'przemo',
            'main',
            'demo-cms',
        ];
    }
}
