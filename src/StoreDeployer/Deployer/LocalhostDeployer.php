<?php

declare(strict_types=1);

namespace App\StoreDeployer\Deployer;

use App\StoreDeployer\Dto\StoreDeploymentResult;
use App\StoreDeployer\ValueObject\StoreDeploymentStatus;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final readonly class LocalhostDeployer implements StoreDeployerInterface
{
    use StorePresetCopierTrait;

    public function __construct(
        #[Autowire(env: 'STORE_DEPLOYER_TARGET_LOCAL_PROJECT_PATH')]
        private string $syliusProjectPath,
        private PathResolver $pathResolver,
        private Filesystem $filesystem,
    ) {
    }

    public function deploy(string $storePresetId): StoreDeploymentResult
    {
        $this->copyPresetToProject($storePresetId, $this->syliusProjectPath);
        $this->runStoreAssembler();

        return new StoreDeploymentResult(status: StoreDeploymentStatus::COMPLETED);
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
