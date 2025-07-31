<?php

declare(strict_types=1);

namespace App\StoreDeployer\Deployer;

use App\StoreDeployer\Dto\StoreDeploymentResult;
use App\StoreDeployer\ValueObject\StoreDeploymentStatus;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final readonly class LocalDeployer implements StoreDeployerInterface
{
    use StorePresetCopierTrait;

    private const STORE_ASSEMBLER_PACKAGE = 'sylius/store-assembler:dev-main';

    public function __construct(
        #[Autowire(env: 'STORE_DEPLOY_TARGET_LOCAL_PROJECT_PATH')]
        private string $syliusProjectPath,
        private PathResolver $pathResolver,
        private Filesystem $filesystem,
    ) {
    }

    public function deploy(string $storePresetId): StoreDeploymentResult
    {
        $this->copyPresetToProject($storePresetId, $this->syliusProjectPath);
        $this->requireStoreAssemblerPackage();
        $this->runStoreAssembler();

        return new StoreDeploymentResult(status: StoreDeploymentStatus::COMPLETED);
    }

    private function requireStoreAssemblerPackage(): void
    {
        Process::fromShellCommandline(sprintf(
            'cd %s && composer require %s --no-scripts --no-interaction',
            escapeshellarg($this->syliusProjectPath),
            escapeshellarg(self::STORE_ASSEMBLER_PACKAGE),
        ))
            ->setTimeout(0)
            ->mustRun()
        ;
    }

    private function runStoreAssembler(): void
    {
        Process::fromShellCommandline(
            command: 'vendor/bin/sylius-store-assembler',
            cwd: $this->syliusProjectPath,
        )
            ->setTimeout(0)
            ->mustRun()
        ;
    }
}
