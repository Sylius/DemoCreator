<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use App\Message\RunPluginManagerMessage;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Process\Process;
use Throwable;

final readonly class LocalhostDeployer implements DemoDeployerInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    public function getProviderKey(): string
    {
        return 'localhost';
    }

    public function deploy(string $environment, array $plugins): array
    {
        $this->messageBus->dispatch(new RunPluginManagerMessage($plugins));

        return [
            'status' => 'success',
            'message' => 'Deployment started',
        ];
    }

    public function getDeployState(string $environment, string $activityId): array
    {
        $command = sprintf(
            'platform activity:get %s --project=%s --property=state',
            escapeshellarg($activityId),
            escapeshellarg($this->projectId),
        );

        return ['status' => trim(Process::fromShellCommandline($command)->mustRun()->getOutput())];
    }

    public function revertDemo(string $environment): void
    {
        $this->deploy($environment, []);
    }
}
