<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use Symfony\Component\Process\Process;

final readonly class PlatformShDeployer implements DemoDeployerInterface
{
    public function __construct(
        private string $projectId,
        private string $syliusBranch,
        private string $platformCliToken,
    ) {}

    public function getProviderKey(): string
    {
        return 'platformsh';
    }

    /**
     * @param string   $slug    A safe, URL-friendly name for the new environment
     * @param string[] $plugins A list of Composer package names to install
     *
     * @return array{environment: string, url: string}
     *
     * @throws DemoDeploymentException
     */
    public function deploy(string $slug, array $plugins): array
    {
        try {
            // 0. Authenticate using API token, no browser interaction
            Process::fromShellCommandline(sprintf(
                'platform auth:api-token-login --token %s --no-interaction',
                escapeshellarg($this->platformCliToken)
            ))->mustRun();

            // 1. Initialize a new child environment by cloning the Sylius-Standard repo
            Process::fromShellCommandline(sprintf(
                'platform environment:init --project=%s --environment=%s --no-interaction %s',
                escapeshellarg($this->projectId),
                escapeshellarg($slug),
                escapeshellarg("https://github.com/Sylius/Sylius-Standard.git#{$this->syliusBranch}")
            ))->mustRun();

            // 2. If plugins are specified, install them via Composer and push the changes
            if (!empty($plugins)) {
                // Install plugins
                $packages = implode(' ', array_map('escapeshellarg', $plugins));
                Process::fromShellCommandline(sprintf(
                    'cd %s && composer require %s',
                    escapeshellarg($slug),
                    $packages
                ))->mustRun();

                // Commit the new dependencies
                Process::fromShellCommandline(sprintf(
                    'cd %s && git add composer.json composer.lock && ' .
                    'git commit -m %s',
                    escapeshellarg($slug),
                    escapeshellarg("Add plugins to demo {$slug}")
                ))->mustRun();

                // Push the updated code to the environment
                Process::fromShellCommandline(sprintf(
                    'platform push --project=%s --environment=%s --no-interaction',
                    escapeshellarg($this->projectId),
                    escapeshellarg($slug)
                ))->mustRun();
            }

            // 3. Retrieve the primary public URL of the new environment
            $urlProcess = Process::fromShellCommandline(sprintf(
                'platform environment:url --project=%s --environment=%s ' .
                '--primary --pipe',
                escapeshellarg($this->projectId),
                escapeshellarg($slug)
            ))->mustRun();

            $url = trim($urlProcess->getOutput());

            return [
                'environment' => $slug,
                'url'         => $url,
            ];
        } catch (DemoDeploymentException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DemoDeploymentException(
                'Deploy failed: ' . $e->getMessage(),
                '',
                $e
            );
        }
    }
}
