<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'metador:reset:superuser',
    description: 'Create new password for root user'
)]
class ResetSuperuserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly \App\Service\User $userService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('password', 'p', InputOption::VALUE_OPTIONAL, 'Default password')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootUser = 'root';
        $rootGroup = 'ROLE_SYSTEM_SUPERUSER';
        $password = $input->getOption('password');

        if ($password === null) {
            $password = $this->userService->generatePassword();
        }

        $user = $this->userService->getByUsername($rootUser);

        if (!$user) {
            $user = new User();
            $user->setUsername($rootUser);
        }

        $user->setPassword($this->userService->encodePassword($user, $password));

        $isSuperuser = false;
        foreach ($user->getGroups() as $group) {
            if ($group->getRole() === $rootGroup) {
                $isSuperuser = true;
            }
        }

        if (!$isSuperuser) {
            $user->addGroup($this->userService->getGroupByName($rootGroup));
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('User     : <info>' . $rootUser . '</info>');
        $output->writeln('Password : <info>' . $password . '</info>');

        return Command::SUCCESS;
    }
}
