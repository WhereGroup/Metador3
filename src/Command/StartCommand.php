<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'metador:start',
    description: 'Start application'
)]
class StartCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%maintenance_file%')]
        private readonly string $maintenanceFile
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Start application');

        $tmpDir = $this->projectDir . '/var/temp/';
        $publicDir = $this->projectDir . '/public/';

        $fs = new Filesystem();

        if (!$fs->exists($tmpDir . 'index.php')) {
            $io->warning('System Datei nicht gefunden!');
            return Command::FAILURE;
        }

        if (!$fs->exists($this->maintenanceFile)) {
            $io->warning('Wartungsdatei nicht gefunden!');
            return Command::FAILURE;
        }

        $fs->remove($publicDir . 'index.php');
        $fs->rename($tmpDir . 'index.php', $publicDir . 'index.php');

        $io->success('Anwendung ist wieder erreichbar.');

        return Command::SUCCESS;
    }
}
