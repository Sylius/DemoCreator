<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use Symfony\Component\Process\Process;
use Throwable;

final readonly class PlatformShDeployer implements DemoDeployerInterface
{
    public function __construct(
        private string $projectId,
        private string $platformCliToken,
    ) {}

    public function getProviderKey(): string
    {
        return 'platformsh';
    }

    public function deploy(string $environment, array $plugins): array
    {
        try {
            // 0. Authenticate if needed
            try {
                Process::fromShellCommandline('platform auth:info')->mustRun();
            } catch (\Throwable) {
                Process::fromShellCommandline(sprintf(
                    'platform auth:api-token-login --token %s --no-interaction',
                    escapeshellarg($this->platformCliToken)
                ))->mustRun();
            }

            Process::fromShellCommandline(
                'rm -fr sylius'
            )->mustRun();

            Process::fromShellCommandline(
                'mkdir -p sylius'
            )->mustRun();

            // 1. Clone Sylius-Standard (branch 'booster') into sylius/<env>
            Process::fromShellCommandline(sprintf(
                'git clone --branch booster %s %s',
                escapeshellarg('https://github.com/Sylius/Sylius-Standard.git'),
                escapeshellarg('sylius')
            ))->mustRun();

            file_put_contents('sylius/sylius-plugins.json', json_encode($plugins, JSON_PRETTY_PRINT));

            // 3. Commit the new config
            Process::fromShellCommandline(sprintf(
                'cd sylius && git add sylius-plugins.json && git commit -m %s',
                escapeshellarg("Add plugin config")
            ))->mustRun();

            // 4. Push to Platform.sh, triggering build + deploy
            Process::fromShellCommandline(sprintf(
                'cd sylius && platform push --project=%s --environment=%s --force --no-wait',
                escapeshellarg($this->projectId),
                escapeshellarg($environment)
            ))->mustRun();

            $url = trim(Process::fromShellCommandline(sprintf(
                'platform environment:url --project=%s --environment=%s --primary --pipe',
                escapeshellarg($this->projectId),
                escapeshellarg($environment)
            ))->mustRun()->getOutput());

            $status = Process::fromShellCommandline(sprintf(
                'platform environment:info --project=%s --environment=%s --format=plain',
                escapeshellarg($this->projectId),
                escapeshellarg($environment)
            ))->mustRun()->getOutput();

            return [
                'status' => $status,
                'url'         => $url,
            ];
        } catch (Throwable $e) {
            Process::fromShellCommandline(
                'rm -fr sylius'
            )->mustRun();

            throw new DemoDeploymentException(
                'Deploy failed: ' . $e->getMessage(),
                '',
            );
        }
    }
}
