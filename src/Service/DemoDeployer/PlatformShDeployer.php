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
        private string $projectDir,
    ) {
    }

    public function getProviderKey(): string
    {
        return 'platformsh';
    }

    public function deploy(string $environment, array $plugins): array
    {
        $syliusDir = $this->projectDir
            . DIRECTORY_SEPARATOR . 'sylius'
            . DIRECTORY_SEPARATOR . $environment;

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
                sprintf('rm -fr %s', escapeshellarg($syliusDir))
            )->mustRun();

            // 1. Clone Sylius-Standard (branch 'booster') into sylius/<env>
            Process::fromShellCommandline(sprintf(
                'git clone --branch booster %s %s',
                escapeshellarg('https://github.com/Sylius/Sylius-Standard.git'),
                escapeshellarg($syliusDir)
            ))->mustRun();

            file_put_contents(
                sprintf('%s/sylius-plugins.json', $syliusDir),
                json_encode($plugins, JSON_PRETTY_PRINT));

            // 3. Commit the new config
            Process::fromShellCommandline(sprintf(
                'cd %s && git add sylius-plugins.json && git commit -m %s',
                escapeshellarg($syliusDir),
                escapeshellarg("Add plugin config")
            ))->mustRun();

            // 4. Push to Platform.sh, triggering build + deploy
            Process::fromShellCommandline(sprintf(
                'cd %s && platform push --project=%s --environment=%s --force --no-wait',
                escapeshellarg($syliusDir),
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
