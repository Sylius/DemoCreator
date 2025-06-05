<?php
declare(strict_types=1);

namespace App\Service\DemoDeployer;

final readonly class DeploymentInitiationResult
{
    public function __construct(
        public string $activityId,
        public string $url,
    ) {
    }
}
