<?php

namespace App\Command;

use App\Service\DemoDeployer\PlatformShDeployer;
use Platformsh\Client\Model\Project;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:deploy',
    description: 'Add a short description for your command',
)]
class DeployCommand extends Command
{
    public function __construct(private PlatformShDeployer $deployer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->deployer->deploy(
            environment: 'booster',
            plugins: [
                "sylius/invoicing-plugin" => "2.0.x-dev",
            ]
        );

        $io = new SymfonyStyle($input, $output);
        $io->title('Deploying to Platform.sh');
        $io->info('Status: ' . $result['status']);
        $io->success('Deployed to ' . $result['url']);

        return Command::SUCCESS;
    }
}
