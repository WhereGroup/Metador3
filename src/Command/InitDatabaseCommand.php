<?php

namespace App\Command;

use App\Component\Utils\Password;
use App\Entity\Group;
use App\Entity\User;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Service\Configuration;
use App\Service\Plugin;
use App\Service\Source;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'metador:init:database',
    description: 'Create system groups and user'
)]
class InitDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TranslatorInterface $translator,
        private readonly UserRepository $userRepository,
        private readonly GroupRepository $groupRepository,
        private readonly Source $sourceService,
        private readonly Configuration $configurationService,
        private readonly Plugin $pluginService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create system groups
        $groups = [
            'ROLE_SYSTEM_SUPERUSER',
            'ROLE_SYSTEM_GEO_OFFICE',
            'ROLE_SYSTEM_GUEST'
        ];

        foreach ($groups as $name) {
            if (!$this->getGroup($name)) {
                $group = new Group();
                $group->setRole($name);
                $this->save($group);
                $output->writeln($this->trans('command_init_db_add_group', ['%group%' => $name]));
            }
        }

        $rootUser = 'root';
        $rootGroup = 'ROLE_SYSTEM_SUPERUSER';

        // Create superuser
        if ($this->getUser($rootUser)) {
            $output->writeln($this->trans('command_init_db_user_exists', ['%user%' => $rootUser]));
        } else {
            $password = Password::generate();
            $user = new User();
            $user->setUsername($rootUser);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->addGroup($this->getGroup($rootGroup));
            $this->save($user);

            $output->writeln($this->trans('command_init_db_add_user', [
                '%user%' => $rootUser,
                '%password%' => $password,
            ]));

            $output->writeln($this->trans('command_init_db_add_group_to_user', [
                '%user%' => $rootUser,
                '%group%' => $rootGroup
            ]));

            $output->writeln('User     : <info>' . $rootUser . '</info>');
            $output->writeln('Password : <info>' . $password . '</info>');
        }

        $this->addSettingsToDatabase();
        $this->addSourceAndProfilesToDatabase();

        return Command::SUCCESS;
    }

    private function addSourceAndProfilesToDatabase(): void
    {
        if ($this->sourceService->count() === 0) {
            $this->sourceService->set('default', 'Metadaten');
        }
    }

    private function addSettingsToDatabase(): void
    {
        $plugins = $this->pluginService->getPlugins();

        foreach ($plugins as $pluginKey => $pluginInfo) {
            if (!isset($pluginInfo['settings'])) {
                continue;
            }

            foreach ($pluginInfo['settings'] as $settingKey => $setting) {
                if (!isset($setting['default'])) {
                    continue;
                }

                $this->configurationService->set($settingKey, $setting['default'], 'plugin', $pluginKey);
            }
        }
    }

    protected function trans(string $string, array $params = []): string
    {
        return $this->translator->trans($string, $params);
    }

    protected function save(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function getUser(string $name): ?User
    {
        return $this->userRepository->findOneBy(['username' => $name]);
    }

    protected function getGroup(string $name): ?Group
    {
        return $this->groupRepository->findOneBy(['role' => $name]);
    }
}
