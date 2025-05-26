<?php

namespace App\MessageHandler;

use App\Message\RunPluginManagerMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class RunPluginManagerMessageHandler
{
    public function __construct(private string $syliusWorkingDir)
    {
    }

    public function __invoke(RunPluginManagerMessage $message): void
    {
        $parsedPlugins = array_map(
            fn(string $name, string $version) => sprintf('--plugins=%s:%s', escapeshellarg($name), escapeshellarg($version)),
            array_keys($message->plugins),
            $message->plugins,
        );

        $parsedPlugins = implode(' ', $parsedPlugins);

        $command = sprintf(
            'php %s/bin/console sylius:plugin-manager %s --mode=auto',
            escapeshellarg($this->syliusWorkingDir),
            $parsedPlugins,
        );

        $process = Process::fromShellCommandline($command, $this->syliusWorkingDir)
            ->setTimeout(0)
            ->mustRun();

        // if it failed, throw so Messenger can retry or move to failure queue
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Plugin-manager exited with code ' . $process->getExitCode());
        }
    }
}
