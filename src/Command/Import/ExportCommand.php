<?php

namespace App\Command\Import;

use App\Component\Utils\Import\Context\DatabaseContext;
use App\Component\Utils\Import\Context\FolderContext;
use App\Component\Utils\Import\ProcessConfigContext;
use App\Component\Utils\Import\ProcessManager;
use App\Component\Utils\Import\Reader\MetadataReader;
use App\Component\Utils\Import\Writer\FolderWriter;
use App\Component\Utils\Stopwatch;
use App\Entity\ImportExport;
use App\Entity\Log;
use App\Service\ConfigurationInterface;
use App\Service\Import\Import as ImportService;
use App\Service\Import\ImportExport as ImportExportService;
use App\Service\Metadata as MetadataService;
use App\Service\MetadataXmlProcessor;
use App\Service\Plugin as PluginService;
use App\Service\Search\DatabaseSearch;
use App\Service\Search\JsonFilterReader;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;


#[AsCommand(
    name: 'metador:export',
    description: 'Metadata export command.'
)]
class ExportCommand extends BasicCommand
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        protected readonly string $projectDir,
        ConfigurationInterface           $configurationService,
        ImportExportService $importExportService,
        protected readonly PluginService $pluginService,
        protected readonly DatabaseSearch $databaseSearch,
        protected readonly ImportService $importService,
        protected readonly MetadataService $metadataService,
        protected readonly MetadataXmlProcessor $metadataXmlProcessor
    ) {
        parent::__construct($configurationService, $importExportService);
    }

    public function getReader(ImportExport $config): MetadataReader
    {
        $readContext = new DatabaseContext();
        $readContext
            ->setService($this->databaseSearch)
            ->setSource($config->getSource())
            ->setFilter($config->getFilter())
        ;

        return (new MetadataReader())->open($readContext);
    }

    public function getWriter(ImportExport $config): FolderWriter
    {

        $writerContext = new FolderContext();

        $writerContext
            ->setFolder($this->getExportFolder($config))
            ->setOverrideFile($config->getRemoveDataOnDestination())
        ;

        return (new FolderWriter())->open($writerContext);
    }

    private function getExportFolder(ImportExport $config): string
    {
        return rtrim($this->importExportService->getExportFolder(), '/') . '/' . $config->getExportFolder();
    }

    protected function startProcessManager(ImportExport $config, $id, OutputInterface $output): void
    {
        $watch = new Stopwatch();
        $search = $this->databaseSearch;
        $search->setSource($config->getSource());

        if (!empty($config->getFilter())) {
            try {
                $search->setExpression(JsonFilterReader::read($config->getFilter(), $search->createExpression()));
            } catch (Throwable $e) {
                $this
                    ->importService
                    ->log($config, Log::ERROR, $e->getMessage());
            }
        }

        try {
            $totalItems = $search->getResultCount();
        } catch (NonUniqueResultException $e) {
            $totalItems = 0;
        }

        $processContext = new ProcessConfigContext($this->getName());
        $processContext
            ->setPartitionSize(1000)
            ->setTotalItemCount($totalItems)
            ->setId($id)
            ->setRange(0)
            ->setRootDir($this->projectDir)
        ;

        $this
            ->importService
            ->log($config, Log::INFO, "Starte Export, es werden $totalItems Metdaten exportiert.");

        (new ProcessManager($processContext))->setProcessLimit(
            $this->configurationService->get('background_process_limit')
        )->run();

        $watch->stop();

        $this
            ->importService
            ->log($config, Log::SUCCESS, "Export abgeschlossen. " . $watch->__toString());
    }

    protected function startProcess(string $limit, string $offset, ImportExport $config): void
    {
        $metadataService = $this->metadataService;
        $import = $this->importService;
        $options = [
            'source'   => $config->getSource(),
            'username' => $config->getUsername(),
            'public'   => true,
            'flush'    => false,
            'log'      => true
        ];

        try {
            $counter = 0;
            $items = [];
            $reader = $this->getReader($config);
            $writer = $this->getWriter($config);

            $generator = $reader->read($offset, $limit);
            while ($generator->valid()) {
                $p = $generator->current();

                if (!$writer->permissionToOverrideFile($p['_uuid'])) {
                    $import->logMetadataExists($config, $p, $options);
                    $generator->next();
                    continue;
                }

                $items[$p['_uuid']] = $this
                    ->metadataXmlProcessor
                    ->objectToXml($p, $this->pluginService->getPluginTemplatePath($p['_profile']) . '/Export/metadata.xml.twig'); // xpath???

                if ($writer->fileExists($p['_uuid'])) {
                    $import->logOverrideMetadata($config, $p, $options);
                } else {
                    $import->logImportMetadata($config, $p, $options);
                }

                if (++$counter >= 50) {
                    $writer->write($items);
                    if ($config->getRemoveSourceData()) {
                        $this->deleteSourceItems($items, $metadataService);
                    }
                    $metadataService->db->flush();
                    $items = [];
                    $counter = 0;
                }

                $generator->next();
            }

            $writer->write($items);
            if ($config->getRemoveSourceData()) {
                $this->deleteSourceItems($items, $metadataService);
            }
        } catch (Exception $e) {
            $import->log($config, Log::ERROR, "Export abgebrochen. " . $e->getMessage());
        }

        $metadataService->db->flush();
    }

    public function deleteSourceItems(array $items, MetadataService $metadataService): void
    {
        foreach ($items as $uuid => $xml) {
            $metadataService->delete($metadataService->findById($uuid), true, false);
        }
        $metadataService->db->dispatchFlush();
    }
}
