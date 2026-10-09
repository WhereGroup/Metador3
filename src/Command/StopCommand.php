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
    name: 'metador:stop',
    description: 'Stop application'
)]
class StopCommand extends Command
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
        $io->title('Stop application');

        $tmpDir = $this->projectDir . '/var/temp/';
        $publicDir = $this->projectDir . '/public/';

        $fs = new Filesystem();

        if ($fs->exists($tmpDir . 'index.php')) {
            $io->warning('Anwendung befindet sich bereits im Wartungsmodus!');
            return Command::FAILURE;
        }

        if (!$fs->exists($publicDir . 'index.php')) {
            $io->warning('System Datei nicht gefunden!');
            return Command::FAILURE;
        }

        if (!$fs->exists($this->maintenanceFile)) {
            $io->warning('Wartungsdatei nicht gefunden!');
            return Command::FAILURE;
        }

        $fs->rename($publicDir . 'index.php', $tmpDir . 'index.php');
        $fs->copy($this->maintenanceFile, $publicDir . 'index.php');

        $io->success('Anwendung befindet sich nun im Wartungsmodus.');

        return Command::SUCCESS;
    }
}
