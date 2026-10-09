<?php

namespace App\Command;

use App\Service\Logger;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\DBAL\Logging\Middleware as DbalLoggingMiddleware;
use Exception;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Event\TaskManagerEvent;

#[AsCommand(
    name: 'metador:taskmanager:run',
    description: 'Runs the Taskmanager'
)]
class TaskManagerCommand extends Command
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Logger $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Ignore lock!')
            ->addOption('token', 't', InputOption::VALUE_REQUIRED, 'Run task with same token.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fs = new Filesystem();
        $filePath = $this->projectDir . '/var/temp/TASKMANAGER.LOCK';

        if (!$input->getOption('force') && $fs->exists($filePath)) {
            $io->warning("Taskmanager is locked. Remove 'var/temp/TASKMANAGER.LOCK' to unlock.");
            $this->log('error', 'Taskmanager kann nicht gestartet werden.');
            return Command::FAILURE;
        }

        $fs->touch($filePath);

        try {
            $this->entityManager->clear();

            // SQL-Logging für den langlaufenden Command deaktivieren
            // (Ersatz für das in DBAL 4 entfernte setSQLLogger(null))
            $dbalConfig = $this->entityManager->getConnection()->getConfiguration();
            $dbalConfig->setMiddlewares([
                ...array_filter(
                    $dbalConfig->getMiddlewares(),
                    static fn ($m) => !$m instanceof DbalLoggingMiddleware
                        && !(class_exists(DebugMiddleware::class) && $m instanceof DebugMiddleware),
                ),
                new DbalLoggingMiddleware(new NullLogger()),
            ]);

            gc_collect_cycles();

            $event = new TaskManagerEvent($input, $output);

            if (!empty($input->getOption('token'))) {
                $event->setToken($input->getOption('token'));
            }

            $io->title("Taskmanager " . (new DateTime())->format('d.m.Y - H:i:s'));
            $this->eventDispatcher->dispatch($event, 'metador.taskmanager');

            foreach ($event->getMessages() as $message) {
                $io->comment($message);
                $this->log('info', $message);
            }
        } catch (Exception $e) {
            $this->log('error', $e->getMessage());
            $io->error($e->getMessage());
            $fs->remove($filePath);
            return Command::FAILURE;
        }

        $fs->remove($filePath);
        $io->success("Done.");

        return Command::SUCCESS;
    }

    private function log(string $type, string $message): void
    {
        $log = $this->logger->newLog();
        $log->setType($type)
            ->setCategory('system')
            ->setSubcategory('taskmanager')
            ->setOperation('start')
            ->setMessage($message)
            ->setUsername('')
        ;
        $this->logger->set($log);
    }
}
