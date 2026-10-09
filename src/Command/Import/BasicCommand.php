<?php

namespace App\Command\Import;

use App\Service\ConfigurationInterface;
use Doctrine\Persistence\ObjectRepository;
use App\Entity\ImportExport;
use App\Service\Import\ImportExport as ImportExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: '',
    description: ''
)]
abstract class BasicCommand extends Command
{
    public function __construct(
        protected readonly ConfigurationInterface $configurationService,
        protected readonly ImportExportService $importExportService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of data to be retrieved.'),
                new InputOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset to start.'),
                new InputArgument('id', InputArgument::REQUIRED, 'Configuration-ID')
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id     = $input->getArgument('id');
        $offset = $input->getOption('offset');
        $limit  = $input->getOption('limit');
        $config = $this->getConfiguration($id);

        if (is_null($offset) || is_null($limit)) {
            $this->startProcessManager($config, $id, $output);
            exit;
        }

        $this->startProcess($limit, $offset, $config);

        return Command::SUCCESS;
    }

    private function getConfiguration($id): ImportExport|ObjectRepository
    {
        /** @var ImportExport $entity */
        return $this
            ->importExportService
            ->get($id);
    }

    protected function startProcess(string $limit, string $offset, ImportExport $config): void
    {
    }

    protected function startProcessManager(ImportExport $config, $id, OutputInterface $output): void
    {
    }
}
