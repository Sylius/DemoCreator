<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\DemoCreationException;
use Symfony\Component\Process\Process;

final readonly class DemoCreator
{
    public function __construct(
        private string $syliusRepo,
        private string $syliusBranch,
        private string $platformProjectId,
    )
    {
    }

    /** @param array<string,string> $plugins */
    public function create(string $slug, array $plugins): array
    {
        $tmp = sys_get_temp_dir() . '/demo_' . $slug . '_' . time();
        mkdir($tmp, recursive: true);

        try {
            $this->run(
                ['git', 'clone', '--branch', $this->syliusBranch, $this->syliusRepo, 'sylius'],
                $tmp,
                step: 'clone'
            );

            $repoDir = $tmp . '/sylius';

            $this->run(
                ['git', 'remote', 'add', 'platform', "ssh://git@ssh.eu.platform.sh:2222/{$this->platformProjectId}.git"],
                $repoDir,
                step: 'add-remote'
            );
            $this->run(['git', 'checkout', '-b', $slug], $repoDir, step: 'checkout');

            file_put_contents(
                $repoDir . '/booster.json',
                json_encode(['plugins' => $plugins], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            );

            $this->run(['git', 'add', 'booster.json'], $repoDir, step: 'git-add');
            $this->run(['git', 'commit', '-m', "Add booster"], $repoDir, step: 'git-commit');

            $this->run(['git', 'push', 'platform', $slug], $repoDir, step: 'git-push');

            $url = trim($this->run(
                ['platform', 'environment:url', $slug, '-p', $this->platformProjectId, '--pipe'],
                cwd: $repoDir,
                step: 'get-url'
            ));

            return ['status' => 'ok', 'url' => $url];

        } finally {
            $this->recursiveRmDir($tmp);
        }
    }

    private function run(array $cmd, string $cwd, string $step): string
    {
        $process = new Process($cmd, cwd: $cwd, timeout: 300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new DemoCreationException(
                sprintf('Step "%s" failed: %s', $step, $process->getErrorOutput()),
                $step
            );
        }
        return $process->getOutput();
    }

    private function recursiveRmDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (new \RecursiveIteratorIterator(
                     new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                     \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
