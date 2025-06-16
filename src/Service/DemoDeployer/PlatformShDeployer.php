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

    public function deploy(string $store, string $environment): DeploymentInitiationResult
    {
        $this->validateStore($store);
        $this->login();

        $syliusDir = $this->getSyliusDirectory($store);
        $this->removeExistingSyliusProject($syliusDir);
        $this->cloneSyliusRepository($syliusDir);
        $this->copyStoreIntoSyliusDirectory($syliusDir, $store);
        $this->commitStore($syliusDir);

        $this->pushSylius($syliusDir, $environment);
        $url = $this->getUrl($environment);
        $deployStateId = $this->getDeployStateId($environment);

        return new DeploymentInitiationResult(
            activityId: $deployStateId,
            url: $url,
        );
    }

    public function getDeployState(string $environment, string $activityId): array
    {
        $command = sprintf(
            'platform activity:get %s --project=%s --property=state',
            escapeshellarg($activityId),
            escapeshellarg($this->projectId),
        );

        return ['status' => trim(Process::fromShellCommandline(trim($command))->mustRun()->getOutput())];
    }

    private function login(): void
    {
        try {
            Process::fromShellCommandline('platform auth:info')->mustRun();
        } catch (\Throwable) {
            Process::fromShellCommandline(sprintf(
                'platform auth:api-token-login --token %s --no-interaction',
                escapeshellarg($this->platformCliToken)
            ))->mustRun();
        }
    }

    private function getSyliusDirectory(string $store): string
    {
        return $this->projectDir
            . DIRECTORY_SEPARATOR . 'sylius'
            . DIRECTORY_SEPARATOR . $store;
    }

    private function removeExistingSyliusProject(string $syliusDir): void
    {
        $result = Process::fromShellCommandline(
            sprintf('rm -fr %s', escapeshellarg($syliusDir))
        )->mustRun();

        if (!$result->isSuccessful()) {
            throw new DemoDeploymentException(sprintf(
                'Failed to remove existing Sylius project at %s: %s',
                $syliusDir,
                $result->getErrorOutput()
            ));
        }
    }

    public function cloneSyliusRepository(string $syliusDir): void
    {
        Process::fromShellCommandline(sprintf(
            'git clone --branch 2.0-store-assembler %s %s',
            escapeshellarg('https://github.com/Sylius/Sylius-Standard.git'),
            escapeshellarg($syliusDir)
        ))->mustRun();
    }

    private function copyStoreIntoSyliusDirectory(string $syliusDir, string $store): void
    {
        $storePath = sprintf('%s/store-templates/%s', $this->projectDir, $store);
        if (!is_dir($storePath)) {
            throw new DemoDeploymentException(sprintf('Store "%s" not found in %s', $store, $storePath));
        }

        $targetDir = $syliusDir . '/store-preset/';
        Process::fromShellCommandline(sprintf(
            'mkdir -p %s',
            escapeshellarg($targetDir)
        ))->mustRun();

        Process::fromShellCommandline(sprintf(
            'cp -R %s/* %s/',
            escapeshellarg($storePath),
            escapeshellarg($targetDir)
        ))->mustRun();
    }

    public function commitStore(string $syliusDir): void
    {
        Process::fromShellCommandline(sprintf(
            'cd %s && git add . && git commit -m "Add store-templates files"',
            escapeshellarg($syliusDir),
        ))->mustRun();
    }

    public function pushSylius(string $syliusDir, string $environment): void
    {
        $result = Process::fromShellCommandline(sprintf(
            'cd %s && platform push --project=%s --environment=%s --force --no-wait',
            escapeshellarg($syliusDir),
            escapeshellarg($this->projectId),
            escapeshellarg($environment)
        ))->mustRun();
    }

    public function getUrl(string $environment): string
    {
        return trim(Process::fromShellCommandline(sprintf(
            'platform environment:url --project=%s --environment=%s --primary --pipe',
            escapeshellarg($this->projectId),
            escapeshellarg($environment)
        ))->mustRun()->getOutput());
    }

    public function getDeployStateId(string $environment): string|false
    {
        return trim(Process::fromShellCommandline(sprintf(
            'platform activities --project=%s --environment=%s --limit 1 --no-header --columns=id --format=plain',
            escapeshellarg($this->projectId),
            escapeshellarg($environment)
        ))->mustRun()->getOutput());
    }

    private function validateStore(string $store): void
    {
        $storePath = sprintf('%s/store-templates/%s', $this->projectDir, $store);

        if (!is_dir($storePath)) {
            throw new DemoDeploymentException(sprintf('Store "%s" not found in %s', $store, $storePath));
        }
    }
}
