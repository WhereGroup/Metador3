<?php

namespace App\Command;

use App\Repository\AddressRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'metador:dump:address',
    description: 'Dumps addresses to filesystem'
)]
class DumpAddressCommand extends Command
{
    private const MAX_RESULTS = 100;

    public function __construct(
        private readonly AddressRepository $addressRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'File to dump in?')
            ->addOption('pretty', null, InputOption::VALUE_NONE, 'Pretty print address')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $io->title('Dump Addresses');

        $file = $input->getArgument('file');
        $pretty = (bool) $input->getOption('pretty');

        // File exists
        if ($fs->exists($file)) {
            $question = new ConfirmationQuestion('Override File?', false, '/^(y|j)/i');

            if (!$helper->ask($input, $output, $question)) {
                return Command::SUCCESS;
            }

            $fs->remove($file);
        }

        $qb = $this->addressRepository
            ->createQueryBuilder('a')
            ->select('a');

        $firstResult = 0;
        $count = 0;

        $fs->touch($file);

        // Dump all documents
        file_put_contents($file, '[');

        do {
            $rows = $qb
                ->setMaxResults(self::MAX_RESULTS)
                ->setFirstResult($firstResult)
                ->getQuery()
                ->getArrayResult();

            foreach ($rows as $row) {
                if (isset($row['id'])) {
                    unset($row['id']);
                }

                if (isset($row['uuid'])) {
                    unset($row['uuid']);
                }

                $json = $pretty ? json_encode($row, JSON_PRETTY_PRINT) : json_encode($row);

                file_put_contents($file, $count !== 0 ? ',' . $json : $json, FILE_APPEND);

                ++$count;
            }

            $firstResult = $firstResult + self::MAX_RESULTS;
        } while (!empty($rows));

        file_put_contents($file, ']', FILE_APPEND);

        $io->success($count . " addresses dumped.");

        return Command::SUCCESS;
    }
}
