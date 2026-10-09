<?php

namespace App\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'metador:test:conventions',
    description: 'Check code for conventions'
)]
class ConventionsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Path to code', 'src/')
            ->addOption('ignore', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Ignore rules', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Conventions check");

        $path = $input->getOption('path');
        $ignore = $input->getOption('ignore');

        if (empty($path)) {
            throw new Exception("Path not found.");
        }

        // TODO: add conventions
        $convention = (new Conventions($path, $ignore))->scan()->showErrors($io);

        if ($convention->hasError()) {
            return Command::FAILURE;
        }

        $io->success("No convention violations found :D");

        return Command::SUCCESS;
    }
}
