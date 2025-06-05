<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

final readonly class LocalhostDeployer implements DemoDeployerInterface
{
    public function deploy(string $store, string $environment): DeploymentInitiationResult
    {

        return new DeploymentInitiationResult(
            activityId: 'activity-id-placeholder',
            url: 'http://localhost:8000', // Placeholder URL
        );
    }

    public function getDeployState(string $environment, string $activityId): array
    {
        return ['status' => 'success'];
    }
}
