<?php

declare(strict_types=1);

namespace App\StoreDeployer\Deployer;

use App\StoreDeployer\Dto\StoreDeploymentResult;
use App\StoreDeployer\Exception\DemoDeploymentException;
use App\StoreDeployer\ValueObject\StoreDeploymentStatus;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

final readonly class PlatformShDeployer implements StoreDeployerInterface
{
    use StorePresetCopierTrait;

//    private const SYLIUS_REPOSITORY = 'https://github.com/Sylius/Sylius-Standard.git';
    private const SYLIUS_REPOSITORY = 'git@github.com:Sylius/Sylius-Standard.git';

    private const SYLIUS_BRANCH = '2.1';

    private const STORE_ASSEMBLER_PACKAGE = 'sylius/store-assembler:dev-main-v4';

    private const PLATFORMSH_ENVIRONMENT_NAME = 'main';

    public function __construct(
        #[Autowire(env: 'PLATFORMSH_PROJECT_ID')] private string $projectId,
        private PathResolver $pathResolver,
        private Filesystem $filesystem,
    ) {
    }

    public function deploy(string $storePresetId): StoreDeploymentResult
    {
        $this->login();
        $this->validateProjectId();
        $this->syliusExists() ? $this->updateSyliusRepository() : $this->cloneSyliusRepository();
        $this->copyPresetToProject($storePresetId, $this->getSyliusProjectDirectory());
        $this->updatePlatformAppYaml();
        $this->requireStoreAssemblerPackage();
        $this->conflictSymfonyUxVersion();
        $this->commitStore($storePresetId);
        $this->pushSylius();

        return new StoreDeploymentResult(
            status: StoreDeploymentStatus::IN_PROGRESS,
            customOptions: [
                'environment' => self::PLATFORMSH_ENVIRONMENT_NAME,
                'activityId' => $this->getActivityId(),

            ]
        );
    }

    private function login(): void
    {
        try {
            Process::fromShellCommandline('platform auth:info')->mustRun();
        } catch (\Throwable) {
            throw new DemoDeploymentException(
                'Missing or invalid Platform.sh CLI authentication. Set up your Platform.sh CLI token first.',
            );
        }
    }

    private function validateProjectId(): void
    {
        if (empty($this->projectId)) {
            throw new DemoDeploymentException('Project ID is not set. Please configure PLATFORMSH_PROJECT_ID.');
        }
        if (!preg_match('/^[a-z0-9-]+$/', $this->projectId)) {
            throw new DemoDeploymentException(sprintf(
                'Invalid project ID "%s". Use only lowercase letters, digits and hyphens.',
                $this->projectId
            ));
        }
    }

    private function getSyliusProjectDirectory(): string
    {
        return Path::join($this->pathResolver->getProjectDirectory(), 'sylius');
    }

    private function syliusExists(): bool
    {
        return is_dir($this->getSyliusProjectDirectory() . '/.git');
    }

    private function cloneSyliusRepository(): void
    {
        $cmd = sprintf(
            'git clone --branch %s --depth 1 %s %s',
            escapeshellarg(self::SYLIUS_BRANCH),
            escapeshellarg(self::SYLIUS_REPOSITORY),
            escapeshellarg($this->getSyliusProjectDirectory())
        );
        Process::fromShellCommandline($cmd)
            ->setTimeout(0)
            ->mustRun();
    }

    private function updateSyliusRepository(): void
    {
        $dir = $this->getSyliusProjectDirectory();
        $cmds = [
            sprintf('cd %s', escapeshellarg($dir)),
            'git fetch --depth 1 origin ' . self::SYLIUS_BRANCH,
            'git checkout ' . self::SYLIUS_BRANCH,
            'git reset --hard origin/' . self::SYLIUS_BRANCH,
            'git clean -fd',
        ];
        Process::fromShellCommandline(implode(' && ', $cmds))
            ->setTimeout(0)
            ->mustRun();
    }

    private function commitStore(string $storePresetId): void
    {
        $message = sprintf('Deploy preset "%s" at %s', $storePresetId, (new \DateTimeImmutable())->format('Y-m-d H:i:s'));
        $cmd = sprintf(
            'cd %s && git add . && git commit -m %s',
            escapeshellarg($this->getSyliusProjectDirectory()),
            escapeshellarg($message)
        );
        Process::fromShellCommandline($cmd)->setTimeout(0)->mustRun();
    }

    private function pushSylius(): void
    {
        $cmd = sprintf(
            'cd %s && platform push --project=%s --environment=%s --force --no-wait',
            escapeshellarg($this->getSyliusProjectDirectory()),
            escapeshellarg($this->projectId),
            escapeshellarg(self::PLATFORMSH_ENVIRONMENT_NAME)
        );
        Process
            ::fromShellCommandline($cmd)
            ->setTimeout(0)
            ->mustRun()
        ;
    }

    public function getUrl(): string
    {
        try {
            return trim(Process::fromShellCommandline(sprintf(
                'platform environment:url --project=%s --environment=%s --primary --pipe',
                escapeshellarg($this->projectId),
                escapeshellarg(self::PLATFORMSH_ENVIRONMENT_NAME)
            ))->mustRun()->getOutput());
        } catch (\Throwable) {
            throw new DemoDeploymentException(sprintf(
                'Failed to get URL for environment "%s" in project "%s". Ensure the environment exists and is active.',
                self::PLATFORMSH_ENVIRONMENT_NAME,
                $this->projectId
            ));
        }
    }

    public function getActivityId(): string|false
    {
        return trim(Process::fromShellCommandline(sprintf(
            'platform activities --project=%s --environment=%s --limit 1 --no-header --columns=id --format=plain',
            escapeshellarg($this->projectId),
            escapeshellarg(self::PLATFORMSH_ENVIRONMENT_NAME)
        ))->mustRun()->getOutput());
    }

    private function updatePlatformAppYaml(): void
    {
        $filePath = $this->getSyliusProjectDirectory() . '/.platform.app.yaml';
        if (!file_exists($filePath)) {
            throw new DemoDeploymentException(sprintf(
                'Platform.sh configuration not found at %s',
                $filePath
            ));
        }

        $config = Yaml::parseFile($filePath);

        foreach (['build', 'deploy'] as $hook) {
            if (isset($config['hooks'][$hook]) && is_string($config['hooks'][$hook])) {
                $config['hooks'][$hook] = explode("\n", $config['hooks'][$hook]);
            }
        }

        if (isset($config['hooks']['build']) && is_array($config['hooks']['build'])) {
            $new = [];
            foreach ($config['hooks']['build'] as $line) {
                $new[] = $line;
                if (str_contains($line, 'yarn install --frozen-lockfile')) {
                    $new[] = 'vendor/bin/sylius-store-assembler --build';
                }
            }
            $config['hooks']['build'] = $new;
        }

        if (isset($config['hooks']['deploy']) && is_array($config['hooks']['deploy'])) {
            $new = [];
            foreach ($config['hooks']['deploy'] as $line) {
                if (str_contains($line, 'bin/console doctrine:database:create --if-not-exists')) {
                    $new[] = 'bin/console doctrine:database:drop --force --if-exists';
                }
                $new[] = $line;
                if (str_contains($line, 'bin/console sylius:fixtures:load -n')) {
                    $new[] = 'vendor/bin/sylius-store-assembler --deploy';
                }
            }
            $config['hooks']['deploy'] = $new;
        }

        foreach (['build', 'deploy'] as $hook) {
            if (isset($config['hooks'][$hook]) && is_array($config['hooks'][$hook])) {
                $config['hooks'][$hook] = implode("\n", $config['hooks'][$hook]);
            }
        }

        $yaml = Yaml::dump($config, 4, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
        $this->filesystem->dumpFile($filePath, $yaml);
    }

    private function requireStoreAssemblerPackage(): void
    {
        $dir = $this->getSyliusProjectDirectory();
        Process::fromShellCommandline(sprintf(
            'cd %s && composer require %s --no-scripts --no-interaction',
            escapeshellarg($dir),
            escapeshellarg(self::STORE_ASSEMBLER_PACKAGE),
        ))
            ->setTimeout(0)
            ->mustRun()
        ;

        Process::fromShellCommandline(sprintf(
            'cd %s && yarn add --dev @symfony/webpack-encore webpack webpack-cli',
            escapeshellarg($dir)
        ))
            ->setTimeout(0)
            ->mustRun()
        ;
    }

    private function conflictSymfonyUxVersion(): void
    {
        $dir = $this->getSyliusProjectDirectory();
        $composerJsonPath = Path::join($dir, 'composer.json');
        if (!file_exists($composerJsonPath)) {
            throw new DemoDeploymentException(sprintf(
                'Composer configuration not found at %s',
                $composerJsonPath
            ));
        }

        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        if (isset($composerJson['conflict']['symfony/ux-twig-component'])) {
            return;
        }

        $composerJson['conflict']['symfony/ux-twig-component'] = '>=2.28.0';
        file_put_contents($composerJsonPath, json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
