<?php

namespace App\Command;

use App\Component\Utils\Import\ProcessContext;
use App\Component\Utils\Import\ProcessManager;
use App\Component\Utils\Stopwatch;
use App\Entity\Address;
use App\Service\Address as AddressService;
use App\Service\ConfigurationInterface;
use App\Service\Search\SolrAddress;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


#[AsCommand(
    name: 'metador:resave:solr_address',
    description: 'This command resaves all addresses at solr'
)]
class ResaveSolrAddressCommand extends Command
{

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        protected readonly string               $projectDir,
        private readonly ConfigurationInterface $configurationService,
        private readonly SolrAddress            $solr,
        protected readonly AddressService       $addressService,
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
            ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $offset = $input->getOption('offset');
        $limit = $input->getOption('limit');

        if (is_null($offset) || is_null($limit)) {
            $this->startProcessManager($output);
            exit;
        }

        $this->startProcess($limit, $offset);

        return Command::SUCCESS;
    }

    /**
     * @param OutputInterface $output
     */
    private function startProcessManager(OutputInterface $output)
    {
        $watch = new Stopwatch();
        $totalItemCount = $this->addressService->count();

        $output->writeln($totalItemCount . " Datensätze werden erneut gespeichert.");

        $processContext = new ProcessContext($this->getName());
        $processContext
            ->setPartitionSize(1000)
            ->setRange(0)
            ->setTotalItemCount($totalItemCount)
            ->setRootDir($this->projectDir);

        (new ProcessManager($processContext))->setProcessLimit(
            $this->configurationService->get('background_process_limit')
        )->run();

        $watch->stop();
        $output->writeln("Erneutes speichern beendet." . $watch->__toString());

    }

    /**
     * @param $limit
     * @param $offset
     * @throws Exception
     */
    private function startProcess($limit, $offset)
    {
        gc_collect_cycles();

        $this->addressService->getManager()->clear();
        $entities = $this->addressService->getRepository()
            ->createQueryBuilder('m')
            ->select('m')
            ->orderBy('m.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        $count = 0;
        /** @var Address $entity */
        foreach ($entities as $entity) {
            $this->solr->update($entity, ['log' => true, 'flush' => false]);
            if (++$count % 500 === 0) {
                $this->solr->client->commit();
            }
        }
        $this->solr->client->commit();
    }
}
