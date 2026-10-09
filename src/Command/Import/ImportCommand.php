<?php

namespace App\Command\Import;

use App\Component\Utils\Import\Context\FolderContext;
use App\Component\Utils\Import\ProcessConfigContext;
use App\Component\Utils\Import\ProcessManager;
use App\Component\Utils\Import\Reader\FolderReader;
use App\Component\Utils\Import\Reader\Reader;
use App\Component\Utils\Stopwatch;
use App\Entity\ImportExport;
use App\Entity\Log;
use App\Service\ConfigurationInterface;
use App\Service\Import\Import as ImportService;
use App\Service\Import\ImportExport as ImportExportService;
use Exception;
use FilesystemIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


#[AsCommand(
    name: 'metador:import',
    description: 'Metadata import command.'
)]
class ImportCommand extends BasicCommand
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        protected readonly string        $projectDir,
        #[Autowire('metador_configuration')]
        protected readonly string        $configuration,
        ConfigurationInterface           $configurationService,
        ImportExportService              $importExportService,
        protected readonly ImportService $importService
    )
    {
        parent::__construct($configurationService, $importExportService);
    }

    public function getReader($config): FolderReader|Reader
    {
        $readContext = new FolderContext();
        $readContext
            ->setName('*.xml')
            ->setFolder($this->getImportFolder($config));

        return (new FolderReader())->open($readContext);
    }

    private function countFiles($folder): int
    {
        return iterator_count(new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS));
    }

    private function getImportFolder(ImportExport $config): string
    {
        return rtrim($this->importExportService->getImportFolder(), '/') . '/' . $config->getImportFolder();
    }

    protected function startProcessManager(ImportExport $config, $id, OutputInterface $output): void
    {
        $watch = new Stopwatch();
        $importFolder = $this->getImportFolder($config);
        $totalItems = $this->countFiles($importFolder);

        $processContext = new ProcessConfigContext($this->getName());
        $processContext
            ->setPartitionSize(1000)
            ->setTotalItemCount($totalItems)
            ->setId($id)
            ->setRootDir($this->projectDir);

        $this
            ->importService
            ->log($config, Log::INFO, "Starte Import, es werden $totalItems Metdaten importiert.");

        (new ProcessManager($processContext))->setProcessLimit(
            $this->configurationService->get('background_process_limit')
        )->run();

        $watch->stop();

        $this
            ->importService
            ->log($config, Log::SUCCESS, "Import abgeschlossen. " . $watch->__toString());
    }

    protected function startProcess(string $limit, $offset, ImportExport $config): void
    {
        $counter = 0;
        $reader = $this->getReader($config);
        $writer = $this->importService;
        $files = [];

        try {
            $generator = $reader->read($offset, $limit);
            while ($generator->valid()) {
                $file = $generator->current();
                $this->addFileToDelete($files, $file, $config);

                $result = $writer->importFromXml($file['content'], $config);

                if (++$counter % 100 === 0) {
                    $writer->db->dispatchFlush();
                    $this->deleteFiles($files, $config);
                }

                $generator->next();
            }
            $writer->db->dispatchFlush();
            $this->deleteFiles($files, $config);
        } catch (Exception $e) {
            $this
                ->importService
                ->log($config, Log::ERROR, "Import abgebrochen. " . $e->getMessage());
        }

        $writer->db->flush();
    }

    private function addFileToDelete(&$files, $file, $config): void
    {
        if (!$config->getRemoveSourceData()) {
            return;
        }

        $files[] = $file['filepath'];
    }

    private function deleteFiles(&$files, $config): void
    {
        if (!$config->getRemoveSourceData()) {
            return;
        }

        foreach ($files as $filepath) {
            $this
                ->importService
                ->log($config, Log::INFO, "Lösche Quelldatei " . $filepath, false);

            unlink($filepath);
        }

        $files = [];
    }
}
