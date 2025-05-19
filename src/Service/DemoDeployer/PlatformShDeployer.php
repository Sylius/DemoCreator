<?php declare(strict_types=1);

namespace App\Service\DemoDeployer;

use App\Exception\DemoDeploymentException;
use Symfony\Component\Process\Process;

final class PlatformShDeployer implements DemoDeployerInterface
{
    public function __construct(
        private string $projectId,
        private string $syliusBranch,
        private string $apiToken,
        private string $sshKeyPath,     // np. '%env(PLATFORMSH_SSH_KEY_PATH)%'
        private string $platformCli = 'platform',
    ) {}

    public function getProviderKey(): string
    {
        return 'platformsh';
    }

    public function deploy(string $slug, array $plugins): array
    {
        // przygotuj GIT_SSH_COMMAND, żeby zawsze używać tego klucza:
        $sshOpts = sprintf(
            'ssh -i %s -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no',
            escapeshellarg($this->sshKeyPath)
        );
        $env = ['GIT_SSH_COMMAND' => $sshOpts];

        try {
            // 0. Zaloguj CLI przez token (bez interakcji)
            $this->runProcess([
                $this->platformCli, 'auth:api-token-login',
                '--api-token=' . $this->apiToken,
            ], null, $env, 'auth');

            // 1. Branch environment
            $this->runProcess([
                $this->platformCli,
                'environment:branch',
                $slug,
                '--project=' . $this->projectId,
                '--parent=' . $this->syliusBranch,
                '--yes',
            ], null, $env, 'branch');

            file_put_contents('booster.json', json_encode(['plugins'=>$plugins], JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
            $this->runProcess(['git','add','booster.json'], null, $env, 'git-add');
            $this->runProcess(['git','commit','-m','"Add booster.json"'], null, $env, 'git-commit');
            $this->runProcess([
                'git','push',
                'ssh://git@ssh.eu.platform.sh:2222/' . $this->projectId . '.git',
                sprintf('HEAD:refs/heads/%s', $slug),
            ], null, $env, 'git-push');

            // 3. Aktywacja (choć platform push też może aktywować od razu)
            $this->runProcess([
                $this->platformCli,
                'environment:activate',
                $slug,
                '--project=' . $this->projectId,
                '--yes',
            ], null, $env, 'activate');

            // 4. Pobranie URL
            $output = $this->runProcess([
                $this->platformCli,
                'environment:url',
                $slug,
                '--project=' . $this->projectId,
                '--pipe',
            ], null, $env, 'url');

            return ['status'=>'ok','url'=>trim($output)];
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

    /**
     * @throws DemoDeploymentException
     */
    private function runProcess(array $cmd, ?string $cwd, array $env, string $step): string
    {
        $process = new Process($cmd, $cwd, $env, null, 600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new DemoDeploymentException(
                $process->getErrorOutput(),
                $process->getStatus(),
            );
        }
        return $process->getOutput();
    }
}
