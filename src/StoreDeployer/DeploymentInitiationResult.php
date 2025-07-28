<?php
declare(strict_types=1);

namespace App\StoreDeployer;

final readonly class DeploymentInitiationResult
{
    public function __construct(
        public string $activityId,
        public string $url,
    ) {
    }
}
