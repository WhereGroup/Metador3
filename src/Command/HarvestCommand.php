<?php

namespace App\Command;


use App\Component\CswHarvester\GetRecordsClient;
use App\Component\Exceptions\MetadataNotFoundException;
use App\Component\Utils\Import\ProcessConfigContext;
use App\Component\Utils\Import\ProcessManager;
use App\Entity\CswHarvester;
use App\Entity\Log;
use App\Service\Browser;
use App\Service\ConfigurationInterface;
use App\Service\Logger;
use App\Service\CswHarvester\CswHarvester as CswHarvesterService;
use App\Service\Metadata as MetadataService;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Throwable;

#[AsCommand(
    name: 'metador:harvest:csw',
    description: 'Metadata import command.'
)]
class HarvestCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string                 $projectDir,
        private readonly ConfigurationInterface $configuration,
        private readonly Browser                $browser,
        private readonly MetadataService        $metadata,
        private readonly Logger                 $logger,
        private readonly CswHarvesterService    $cswHarvesterService
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum number of data to be retrieved.'),
                new InputOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset to start.'),
                new InputArgument('id', InputArgument::REQUIRED, 'ID of CswHarvester configuration.')
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = $input->getArgument('id');
        $offset = $input->getOption('offset');
        $limit = $input->getOption('limit');
        $config = $this->cswHarvesterService->get($id);

        // Start Process-Manager
        if (is_null($offset) || is_null($limit)) {
            $url = GetRecordsClient::getHitsUrl($config->getUrl(), $config->getFilter() ?? []);
            $this->log(Log::INFO, $config, "Hits werden von der URL: " . GetRecordsClient::getHitsUrl($config->getUrl(), $config->getFilter() ?? []) . " geharvestet.");
            $totalItems = GetRecordsClient::getHits($this->browser, $config->getUrl(), $config->getFilter() ?? []);

            $this->log(Log::INFO, $config, "Es werden " . $totalItems . " geharvestet.");

            $processContext = new ProcessConfigContext($this->getName());
            $processContext
                ->setPartitionSize(100)
                ->setTotalItemCount($totalItems)
                ->setId($id)
                ->setRootDir($this->projectDir);

            (new ProcessManager($processContext))->setProcessLimit(
                $this->configuration->get('background_process_limit')
            )->run();

            $this->log(Log::INFO, $config, "Harvest beendet.");
            exit;
        }

        // Run Process
        try {
            $this->metadata->db->clearSqlObjectManager();
            $processor = $this->metadata->getProcessor();
            $this->log(Log::INFO, $config, "Results werden von der URL: "
                . GetRecordsClient::getResultssUrl($config->getUrl(), $config->getFilter() ?? [], $limit, $offset) . " geharvestet.");

            $records = GetRecordsClient::getRecords($this->browser, $config->getUrl(), $config->getFilter() ?? [], $limit, $offset);

            $count = 0;
            foreach ($records as $record) {
                $p = $processor->xmlToObjectByMapping($record, $config->getProfileMapping());

                if (++$count % 100 === 0) {
                    $this->metadata->db->dispatchFlush();
                }
                try {
                    $entity = $this->metadata->findById($p['_uuid']);
                } catch (MetadataNotFoundException $e) {
                    $entity = null;
                } catch (Throwable $e) {
                    $this->log(Log::ERROR, $config, 'Datensatz kann nicht importiert werden. ' . $e->getMessage());
                }

                if (!$entity) {
                    $this->metadata->insertByObject($p, [
                        'public' => true,
                        'source' => $config->getSource(),
                        'username' => $config->getUsername(),
                        'flush' => false,
                    ]);
                    continue;
                }

                if ($entity->getSource() != $config->getSource()) {
                    $this->log(Log::WARNING, $config, 'Datensatz "' . $entity->getTitle()
                        . '" mit der UUID "' . $entity->getId()
                        . '" befindet sich in einer anderen Datenquelle und wird nicht importiert.',
                        false
                    );
                }

                $this->metadata->updateByObject($p, [
                    'public' => true,
                    'source' => $config->getSource(),
                    'username' => $config->getUsername(),
                    'flush' => false,
                ]);
            }
            $this->metadata->db->dispatchFlush();
        } catch (Exception $e) {
            $this->log(Log::ERROR, $config, "Harvest-Process wurde abgebrochen. " . $e->getMessage());
        }

        $this->metadata->db->dispatchFlush();

        return Command::SUCCESS;
    }

    private function log(string $type, CswHarvester $config, string $message, $flush = true): void
    {
        $log = $this->logger->newLog();
        $log->setType($type)
            ->setCategory('CSW')
            ->setSubcategory('Harvester')
            ->setOperation('Harvest')
            ->setMessage($message)
            ->setSource($config->getSource())
            ->setUsername($config->getUsername())
            ->setIdentifier($config->getId())
            ->setPath('metador_admin_harvester_edit', ['id' => $config->getId()]);
        $this->logger->set($log);
    }
}
