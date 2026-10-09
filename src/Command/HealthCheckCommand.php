<?php

namespace App\Command;

use App\Service\HealthCheck;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'metador:health-check',
    description: 'Determine the health of the application'
)]
class HealthCheckCommand extends Command
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly HealthCheck $healthCheck,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $event = $this->healthCheck->check();

        $io->title($this->translator->trans('healthcheck_title'));

        if ($event->hasError()) {
            if ($input->getOption('quiet')) {
                throw new Exception($this->translator->trans("healthcheck_error_message"));
            }
            $io->warning($this->translator->trans("healthcheck_error_message"));
        } else {
            $io->success($this->translator->trans("healthcheck_success_message"));
        }

        if (!empty($event->getLogs())) {
            $rows = [];

            foreach ($event->getLogs() as $log) {
                if ($input->getOption('verbose') === false && $log->getResult() !== 'error') {
                    continue;
                }

                $rows[] = [strtoupper($log->getResult()), $log->getOrigin(), $log->getMessage()];
            }

            if (!empty($rows)) {
                $table = new Table($output);
                $table
                    ->setHeaders([
                        '#',
                        $this->translator->trans('healthcheck_table_origin'),
                        $this->translator->trans('healthcheck_table_message')
                    ])
                    ->setRows($rows)
                ;
                $table->render();
            }
        }

        return $event->hasError() ? Command::FAILURE : Command::SUCCESS;
    }
}
