<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use App\Exception\InvalidStorePresetException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

final readonly class PlatformShDeployer implements DemoDeployerInterface
{
    public function __construct(
        #[Autowire('%platformsh.project_id%')] private string $projectId,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
        #[Autowire('%kernel.project_dir%/var/store-presets')] private string $presetsDir,
    ) {
    }

    public function deploy(string $store, string $environment): DeploymentInitiationResult
    {
        $this->login();
        $this->validateProjectId();

        $this->removeExistingSyliusProject();
        $this->cloneSyliusRepository();
        $this->updatePlatformAppYaml();
        $this->copyStorePresetIntoSyliusDirectory($store);
        $this->requireStoreAssembler();
        $this->commitStore();

        $this->pushSylius($environment);
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
        } catch (\Throwable $e) {
            throw new DemoDeploymentException('Missing of invalid Platform.sh CLI authentication. Set up your Platform.sh CLI token first.', 0, $e);
        }
    }

    private function getSyliusDirectory(): string
    {
        return $this->projectDir . DIRECTORY_SEPARATOR . 'sylius';
    }

    private function removeExistingSyliusProject(): void
    {
        $result = Process::fromShellCommandline(
            sprintf('rm -fr %s', escapeshellarg($this->getSyliusDirectory()))
        )->mustRun();

        if (!$result->isSuccessful()) {
            throw new DemoDeploymentException('Failed to remove existing Sylius project');
        }
    }

    public function cloneSyliusRepository(): void
    {
        Process::fromShellCommandline(sprintf(
            'git clone --branch 2.0 %s %s',
            escapeshellarg('https://github.com/Sylius/Sylius-Standard.git'),
            escapeshellarg($this->getSyliusDirectory())
        ))->mustRun();
    }

    private function copyStorePresetIntoSyliusDirectory(string $store): void
    {
        $targetDir = $this->getSyliusDirectory() . DIRECTORY_SEPARATOR . 'store-preset';
        Process::fromShellCommandline(sprintf(
            'mkdir -p %s',
            escapeshellarg($targetDir)
        ))->mustRun();

        $sourceDir = $this->presetsDir . DIRECTORY_SEPARATOR . $store;
        if (!is_dir($sourceDir)) {
            throw new InvalidStorePresetException(sprintf(
                'Store preset "%s" not exists in %s. Check the passed store name or ensure that the preset exists.',
                $store,
                $this->presetsDir,
            ));
        }

        Process::fromShellCommandline(sprintf(
            'cp -R %s/. %s',
            escapeshellarg($sourceDir),
            escapeshellarg($targetDir)
        ))->mustRun();
    }

    public function commitStore(): void
    {
        Process::fromShellCommandline(sprintf(
            'cd %s && git add . && git commit -m "Add store-preset files"',
            escapeshellarg($this->getSyliusDirectory()),
        ))->mustRun();
    }

    public function pushSylius(string $environment): void
    {
        $result = Process::fromShellCommandline(sprintf(
            'cd %s && platform push --project=%s --environment=%s --force --no-wait',
            escapeshellarg($this->getSyliusDirectory()),
            escapeshellarg($this->projectId),
            escapeshellarg($environment)
        ))->mustRun();
    }

    public function getUrl(string $environment): string
    {
        try {
            return trim(Process::fromShellCommandline(sprintf(
                'platform environment:url --project=%s --environment=%s --primary --pipe',
                escapeshellarg($this->projectId),
                escapeshellarg($environment)
            ))->mustRun()->getOutput());
        } catch (\Throwable $e) {
            throw new DemoDeploymentException(sprintf(
                'Failed to get URL for environment "%s" in project "%s". Ensure the environment exists and is active.',
                $environment,
                $this->projectId
            ), 0, $e);
        }
    }

    public function getDeployStateId(string $environment): string|false
    {
        return trim(Process::fromShellCommandline(sprintf(
            'platform activities --project=%s --environment=%s --limit 1 --no-header --columns=id --format=plain',
            escapeshellarg($this->projectId),
            escapeshellarg($environment)
        ))->mustRun()->getOutput());
    }

    private function getStorePresetPath(string $store): string
    {
        $path = $this->projectDir . '/store-presets/' . $store;

        if (!is_dir($path)) {
            $presets = array_diff(
                scandir($this->projectDir . '/store-presets'),
                ['.', '..']
            );
            $message = sprintf(
                'Store preset "%s" not found at %s. Available presets: %s',
                $store,
                $path,
                implode(', ', $presets)
            );
            throw new InvalidStorePresetException($message);
        }

        return $path;
    }

    private function updatePlatformAppYaml(): void
    {
        $filePath = $this->getSyliusDirectory() . DIRECTORY_SEPARATOR . '.platform.app.yaml';
        if (!file_exists($filePath)) {
            throw new DemoDeploymentException(sprintf(
                'Platform.sh configuration not found at %s',
                $filePath
            ));
        }

        $config = Yaml::parseFile($filePath);

        // Ensure hooks are arrays
        foreach (['build', 'deploy'] as $hook) {
            if (isset($config['hooks'][$hook]) && is_string($config['hooks'][$hook])) {
                $config['hooks'][$hook] = explode("\n", $config['hooks'][$hook]);
            }
        }

        // BUILD: add vendor/bin/sylius-store-assembler --build after yarn install --frozen-lockfile
        if (isset($config['hooks']['build']) && is_array($config['hooks']['build'])) {
            $build = $config['hooks']['build'];
            $newBuild = [];
            foreach ($build as $line) {
                $newBuild[] = $line;
                if (str_contains($line, 'yarn install --frozen-lockfile')) {
                    $newBuild[] = 'vendor/bin/sylius-store-assembler --build';
                }
            }
            $config['hooks']['build'] = $newBuild;
        }

        // DEPLOY: add vendor/bin/sylius-store-assembler --deploy after bin/console sylius:fixtures:load -n
        // and bin/console doctrine:database:drop --force --if-exists before bin/console doctrine:database:create --if-not-exists
        if (isset($config['hooks']['deploy']) && is_array($config['hooks']['deploy'])) {
            $deploy = $config['hooks']['deploy'];
            $newDeploy = [];
            foreach ($deploy as $i => $line) {
                // Before database:create
                if (str_contains($line, 'bin/console doctrine:database:create --if-not-exists')) {
                    $newDeploy[] = 'bin/console doctrine:database:drop --force --if-exists';
                }
                $newDeploy[] = $line;
                // After fixtures:load
                if (str_contains($line, 'bin/console sylius:fixtures:load -n')) {
                    $newDeploy[] = 'vendor/bin/sylius-store-assembler --deploy';
                }
            }
            $config['hooks']['deploy'] = $newDeploy;
        }

        // Zamień tablice hooków z powrotem na stringi (Platform.sh wymaga stringów)
        if (isset($config['hooks']['build']) && is_array($config['hooks']['build'])) {
            $config['hooks']['build'] = implode("\n", $config['hooks']['build']);
        }
        if (isset($config['hooks']['deploy']) && is_array($config['hooks']['deploy'])) {
            $config['hooks']['deploy'] = implode("\n", $config['hooks']['deploy']);
        }

        $yamlContent = Yaml::dump($config, 4, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        file_put_contents($filePath, $yamlContent);
    }

    private function validateProjectId()
    {
        if (empty($this->projectId)) {
            throw new DemoDeploymentException('Project ID is not set. Please configure the project ID in the environment variables.');
        }

        if (!preg_match('/^[a-z0-9-]+$/', $this->projectId)) {
            throw new DemoDeploymentException(sprintf(
                'Invalid project ID "%s". It should only contain lowercase letters, numbers, and hyphens.',
                $this->projectId
            ));
        }
    }

    private function requireStoreAssembler(): void
    {
        Process::fromShellCommandline(sprintf(
            'cd %s && composer require sylius/store-assembler --no-scripts --no-interaction',
            escapeshellarg($this->getSyliusDirectory())
        ))->mustRun();

        Process::fromShellCommandline(sprintf(
            'cd %s && yarn add --dev @symfony/webpack-encore webpack webpack-cli',
            escapeshellarg($this->getSyliusDirectory())
        ))->mustRun();
    }
}
