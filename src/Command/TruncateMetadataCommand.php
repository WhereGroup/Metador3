<?php

namespace App\Command;

use App\Service\Metadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Event\MetadataChangeEvent;
use App\Entity\Metadata as MetadataEntity;

#[AsCommand(
    name: 'metador:truncate:metadata',
    description: 'Truncates all Metadata'
)]
class TruncateMetadataCommand extends Command
{
    public function __construct(
        private readonly Metadata $metadata
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($io->confirm("Metadaten löschen?", false)) {
            $this->metadata->db->getRepository()->truncate();
            $this->metadata->db->dispatchPostTruncate(
                new MetadataChangeEvent(new MetadataEntity(), [])
            );
            $io->success("Alle Metadaten wurde erfolgreich gelöscht.");
        }

        return Command::SUCCESS;
    }
}
