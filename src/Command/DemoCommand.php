<?php

namespace App\Command;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Laravel\Prompts\{text, confirm, multiselect, progress, spin};
use function Termwind\render;

#[AsCommand(
    name: 'app:demo',
    description: 'Demonstrates rich CLI features using Laravel Prompts and Termwind'
)]
class DemoCommand extends Command
{

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Interaktywne Prompts:
        $name = text('Jak masz na imię?', required: true, hint: 'Twój stary pijany');
        $agree = confirm('Uruchomić demo?', default: true);
        if (! $agree) { $io->warning('Przerwano.'); return Command::SUCCESS; }

        // Spinner + symulacja pracy:
        spin(fn() => usleep(600000), 'Ładowanie modułów...');

        // Progress bar (Prompts) dla kroków:
        progress(label: 'Przetwarzanie', steps: 5, callback: function ($step) {
            usleep(200000);
        });

        // Tabela (Symfony Table):
        (new Table($output))
            ->setHeaders(['Klucz', 'Wartość'])
            ->setRows([['User', $name], ['Status', 'OK']])
            ->render();

        // Finał w stylu SymfonyStyle:
        $io->success('Gotowe!');

        return Command::SUCCESS;
    }
}
//
//$app = new Application('cli-tool', '0.1.0');
//$app->add(new DemoCommand());
//$app->setDefaultCommand('demo', true);
//$app->run();
