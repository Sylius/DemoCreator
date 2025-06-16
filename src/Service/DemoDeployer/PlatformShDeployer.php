<?php

declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use App\Exception\InvalidStorePresetException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

final readonly class PlatformShDeployer implements DemoDeployerInterface
{
    public function __construct(
        private string $projectId,
        private string $projectDir,
    ) {
    }

    public function deploy(string $store, string $environment): DeploymentInitiationResult
    {
        $this->login();
        $this->validateProjectId();

        $syliusDir = $this->getSyliusDirectory($store);
        $this->removeExistingSyliusProject($syliusDir);
        $this->cloneSyliusRepository($syliusDir);
        $this->updatePlatformAppYaml($syliusDir);
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
        } catch (\Throwable $e) {
            throw new DemoDeploymentException('Missing of invalid Platform.sh CLI authentication. Set up your Platform.sh CLI token first.', 0, $e);
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
            'git clone --branch 2.0 %s %s',
            escapeshellarg('https://github.com/Sylius/Sylius-Standard.git'),
            escapeshellarg($syliusDir)
        ))->mustRun();
    }

    private function copyStoreIntoSyliusDirectory(string $syliusDir, string $store): void
    {
        $storePath = $this->getStorePresetPath($store);
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
            'cd %s && git add . && git commit -m "Add store-preset files"',
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

    private function updatePlatformAppYaml(string $syliusDir): void
    {
        $filePath = $syliusDir . '/.platform.app.yaml';
        if (!file_exists($filePath)) {
            throw new DemoDeploymentException(sprintf(
                'Platform.sh configuration not found at %s',
                $filePath
            ));
        }

        $config = Yaml::parseFile($filePath);

        $hooksConfig = [
            'build' => [
                [
                    'search' => 'yarn install --frozen-lockfile',
                    'offset' => 0,
                    'lines' => [
                        'composer config repositories.sylius-assembler vcs git@github.com:Sylius/StoreAssembler.git',
                        'composer require sylius/store-assembler:dev-main',
                        'vendor/bin/store-assembler --build',
                    ],
                ],
            ],
            'deploy' => [
                [
                    'search' => 'bin/console sylius:fixtures:load -n',
                    'offset' => 1,
                    'lines' => ['vendor/bin/store-assembler --deploy'],
                ],
                [
                    'search' => 'bin/console doctrine:database:create --if-not-exists',
                    'offset' => 0,
                    'lines' => ['bin/console doctrine:database:drop --force --if-exists'],
                ],
            ],
        ];

        foreach ($hooksConfig as $hookName => $operations) {
            if (!isset($config['hooks'][$hookName])) {
                continue;
            }
            $lines = explode("\n", $config['hooks'][$hookName]);
            foreach ($operations as $op) {
                $pos = null;
                foreach ($lines as $i => $line) {
                    if (str_contains($line, $op['search'])) {
                        $pos = $i;
                        break;
                    }
                }
                if ($pos !== null) {
                    array_splice($lines, $pos + $op['offset'], 0, $op['lines']);
                }
            }
            $config['hooks'][$hookName] = implode("\n", $lines);
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
}
