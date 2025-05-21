<?php

declare(strict_types=1);

namespace App\Service\Provider;

final class DemoConfigProvider
{
    public function getPlugins(): array
    {
        return [
            'sylius/b2b-kit',
            'sylius/return-plugin',
            'sylius/cms-plugin',
            'sylius/invoicing-plugin',
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
            'main',
            'demo-b2b',
            'demo-cms',
        ];
    }
}
