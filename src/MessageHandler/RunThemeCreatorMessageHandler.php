<?php

namespace App\MessageHandler;

use App\Message\RunPluginManagerMessage;
use App\Message\RunThemeCreatorMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class RunThemeCreatorMessageHandler
{
    public function __construct(private string $syliusWorkingDir)
    {
    }

    public function __invoke(RunThemeCreatorMessage $message): void
    {

        $parsedThemes = array_map(
            fn(string $name, string $version) => sprintf('--themes=%s:%s', escapeshellarg($name), escapeshellarg($version)),
            array_keys($message->themes),
            $message->themes,
        );

        $command = sprintf(
            'php %s/bin/console sylius:plugin-manager %s --mode=auto',
            escapeshellarg($this->syliusWorkingDir),
            $parsedThemes,
        );

        $process = Process::fromShellCommandline($command, $this->syliusWorkingDir)
            ->setTimeout(0)
            ->mustRun();

        // if it failed, throw so Messenger can retry or move to failure queue
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Theme creator exited with code ' . $process->getExitCode());
        }
    }
}
