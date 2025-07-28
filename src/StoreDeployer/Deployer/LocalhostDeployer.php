<?php

declare(strict_types=1);

namespace App\StoreDeployer\Deployer;

use App\StoreDesigner\Exception\StorePresetNotFoundException;
use App\StoreDesigner\Util\PathResolver;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Process;

final readonly class LocalhostDeployer implements StoreDeployerInterface
{
    public function __construct(
        #[Autowire(env: 'STORE_DEPLOYER_TARGET_LOCAL_PROJECT_PATH')]
        private string $syliusProjectPath,
        private PathResolver $pathResolver,
        private Filesystem $filesystem,
    ) {
    }

    public function deploy(string $storePresetId): StoreDeploymentStatus
    {
        $this->copyPresetToProject($storePresetId);
        $this->runStoreAssembler();

        return StoreDeploymentStatus::COMPLETED;
    }

    private function copyPresetToProject(string $storePresetId): void
    {
        $sourcePath = $this->pathResolver->getStorePresetRootDirectory($storePresetId);
        $destinationPath = Path::join($this->syliusProjectPath, 'store-preset');

        if (!is_dir($sourcePath)) {
            throw new StorePresetNotFoundException(
                sprintf('Store preset "%s" not found in "%s".', $storePresetId, $sourcePath)
            );
        }

        if (!is_dir($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $this->filesystem->mirror(
            originDir: $sourcePath,
            targetDir: $destinationPath,
            options: [
                'override' => true,
                'delete' => true,
            ]
        );
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
