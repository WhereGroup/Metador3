<?php

namespace App\Command;

use App\Repository\LogRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'metador:dump:logs',
    description: 'Dumps logs to filesystem'
)]
class BackupCommand extends Command
{
    private const MAX_RESULTS = 100;

    public function __construct(
        private readonly LogRepository $logRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('folder', InputArgument::REQUIRED, 'Folder to save old logs in.')
            ->addOption('delete', 'd', InputOption::VALUE_NONE, 'Delete dumped files from database!')
            ->addOption('older-than', 'o', InputOption::VALUE_OPTIONAL, 'Dump logs older than n months.')
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $fs = new Filesystem();
        $io = new SymfonyStyle($input, $output);
        $io->title('Dump Logs');

        $folder = $input->getArgument('folder');

        if (!$fs->exists($folder)) {
            $io->error("Folder does not exists!");
            return Command::FAILURE;
        }

        $olderThan = $input->getOption('older-than');

        $qb = $this->logRepository
            ->createQueryBuilder('m')
            ->select('m');

        if ($olderThan) {
            $qb
                ->where('m.dateTime < :date')
                ->setParameter(
                    'date',
                    new \DateTime('-' . $olderThan . ' month'),
                    Types::DATETIME_MUTABLE
                );
        }

        $firstResult = 0;
        $count = 0;

        $date = (new \DateTime())->format('YmdHis');
        $file = rtrim($folder, '/') . '/' . $date . '.log';

        file_put_contents($file, '[');

        do {
            $rows = $qb
                ->orderBy('m.id')
                ->getQuery()
                ->setMaxResults(self::MAX_RESULTS)
                ->setFirstResult($firstResult)
                ->getArrayResult();

            foreach ($rows as $row) {
                if (isset($row['dateTime']) && $row['dateTime'] instanceof \DateTimeInterface) {
                    $row['dateTime'] = $row['dateTime']->format('Y-m-d H:i');
                }

                $json = json_encode($row);
                file_put_contents($file, $count !== 0 ? ',' . $json : $json, FILE_APPEND);
                ++$count;
            }

            $firstResult += self::MAX_RESULTS;
        } while (!empty($rows));

        file_put_contents($file, ']', FILE_APPEND);

        if ($input->getOption('delete')) {
            $deleteQb = $this->logRepository
                ->createQueryBuilder('l')
                ->delete();

            if ($olderThan) {
                $deleteQb
                    ->where('l.dateTime < :date')
                    ->setParameter(
                        'date',
                        new \DateTime('-' . $olderThan . ' month'),
                        Types::DATETIME_MUTABLE
                    );
            }

            $deleteQb->getQuery()->execute();
        }

        $io->success($count . " logs dumped.");

        return Command::SUCCESS;
    }
}
