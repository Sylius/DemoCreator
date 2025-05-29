<?php

namespace App\MessageHandler;

use App\Message\CreateDemoMessage;
use App\Message\RunPluginManagerMessage;
use App\Message\RunThemeCreatorMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;

#[AsMessageHandler]
final readonly class CreateDemoMessageHandler
{
    public function __construct(private string $syliusWorkingDir)
    {
    }

    public function __invoke(CreateDemoMessage $message): void
    {



        $process = Process::fromShellCommandline($command, $this->syliusWorkingDir)
            ->setTimeout(0)
            ->mustRun();

        // if it failed, throw so Messenger can retry or move to failure queue
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('Theme creator exited with code ' . $process->getExitCode());
        }
    }
}
