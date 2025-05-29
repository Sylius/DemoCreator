<?php

namespace App\Command;

use App\Service\DemoDeployer\LocalhostDeployer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:deploy',
    description: 'Deploys the booster configuration',
)]
class DeployCommand extends Command
{
    private string $projectDir;

    public function __construct(
        private readonly LocalhostDeployer $deployer,
        KernelInterface $kernel
    ) {
        parent::__construct();
        $this->projectDir = $kernel->getProjectDir();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('config', InputArgument::OPTIONAL, 'Path to the booster.json file', 'demo-creator-templates/booster.json')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $relativePath = $input->getArgument('config');
        $configPath = $this->projectDir . '/' . ltrim($relativePath, '/');

        if (!file_exists($configPath)) {
            $io->error(sprintf('Configuration file not found: %s', $configPath));
            return Command::FAILURE;
        }

        $io->title('Loading booster configuration');

        // Load and decode JSON
        $json = file_get_contents($configPath);
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON in ' . $configPath . ': ' . json_last_error_msg());
            return Command::FAILURE;
        }

        // Extract nested sections
        $plugins = $data['plugins'] ?? [];
        $themes = $data['themes'] ?? [];

        $io->section('Plugins to deploy');
        foreach ($plugins as $name => $version) {
            $io->writeln(sprintf('- %s: %s', $name, $version));
        }

        $io->section('Theme settings');
        $io->writeln(json_encode($themes, JSON_PRETTY_PRINT));

        // Perform deployment
        $io->title('Deploying to localhost');
        $result = $this->deployer->deploy(
            environment: $data['environment'] ?? 'booster',
            plugins: $plugins,
            themes: $themes,
        );

        $io->success('Deployment finished.');

        return Command::SUCCESS;
    }
}
