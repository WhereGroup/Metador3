<?php

namespace App\Command;

use App\Service\Search\SolrAddress;
use App\Service\Search\SolrMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'metador:truncate:solr',
    description: 'Truncate solr data'
)]
class TruncateSolrCommand extends Command
{

    public function __construct(
        private readonly SolrMetadata $metadata,
        private readonly SolrAddress  $address,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDefinition([
                new InputArgument('core', InputArgument::OPTIONAL, 'Solr Core name: "metadata" or "address"')
            ]);
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $core = $input->getArgument('core');
        $output->write('Solr-Daten werden' . ($core ? ' für Core "' . $core . '"': '') . ' gelöscht... ');
        if (!$core || $core === 'metadata') {
            $this->metadata->client->deleteByQuery('*:*');
            $this->metadata->client->commit();
        }
        if (!$core || $core === 'address') {
            $this->address->client->deleteByQuery('*:*');
            $this->address->client->commit();
        }

        $output->writeln("fertig.");

        return Command::SUCCESS;
    }
}
