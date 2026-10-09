<?php

namespace App\Command;

use App\Entity\Address;
use App\Component\Exceptions\MetadorException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'metador:import:address',
    description: 'Imports addresses from file'
)]
class ImportAddressCommand extends Command
{
    public function __construct(
        private readonly \App\Service\Address $addressService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'File to import from?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Addresses');

        $file = $input->getArgument('file');

        if (!$fs->exists($file) || !is_readable($file)) {
            $io->error('File not found.');
            return Command::FAILURE;
        }

        $rows = json_decode(file_get_contents($file), true);

        if (!$rows) {
            $io->error('No addresses found');
            return Command::FAILURE;
        }

        $count = 0;

        foreach ($rows as $row) {
            try {
                $entity = new Address();
                $entity
                    ->setIndividualName($row['individualName'])
                    ->setOrganisationName($row['organisationName'])
                    ->setPositionName($row['positionName'])
                    ->setEmail($row['email'])
                    ->setCountry($row['country'])
                    ->setAdministrativeArea($row['administrativeArea'])
                    ->setDeliveryPoint($row['deliveryPoint'])
                    ->setCity($row['city'])
                    ->setPostalCode($row['postalCode'])
                    ->setVoice($row['voice'])
                    ->setFacsimile($row['facsimile'])
                    ->setUrl($row['url'])
                    ->setUrlDescription($row['urlDescription'])
                    ->setHoursOfService($row['hoursOfService']);

                $this->addressService->save($entity);
                ++$count;
            } catch (MetadorException $e) {
                $io->error($e->getMessage());
            }
        }

        $io->success($count . " addresses imported.");

        return Command::SUCCESS;
    }
}
