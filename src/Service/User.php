<?php

namespace App\Service;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\NoResultException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Group;
use App\Entity\User as UserEntity;
use App\Component\Exceptions\MetadorException;
use Doctrine\ORM\EntityManagerInterface;

class User implements UserInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $encoder,
        private readonly UserRepository $repo,
        private readonly GroupRepository $groupRepo,
        private readonly Security $security,
    ) {

    }

    public function count(): int
    {
        try {
            return $this->repo->countAll();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    public function countGroups(): int
    {
        try {
            return $this->groupRepo->countAll();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    public function get($id)
    {
        $user = $this->repo->findOneById($id);

        if (!$user) {
            throw new MetadorException("Benutzer nicht gefunden.");
        }

        return $user;
    }

    public function getByUsername($username)
    {
        return $this->repo->findOneByUsername($username);
    }

    public function getGroupByName($groupname)
    {
        return $this->groupRepo->findOneByRole($groupname);
    }

    public function findAll(): array
    {
        return $this->repo->findAllSorted();
    }

    public function insert(UserEntity $user): static
    {
        if ($this->repo->findOneByUsername($user->getUsername())) {
            throw new MetadorException("Benutzer bereits vorhanden.");
        }

        $user->setPassword($this->encodePassword($user, $user->getPassword()));

        $this->update($user);

        return $this;
    }

    public function createIfNotExists($username, $password, $email = '', $groups = []): static
    {
        if (!$this->getByUsername($username)) {
            $user = new UserEntity();

            $user
                ->setUsername($username)
                ->setPassword($password)
                ->setEmail($email)
            ;

            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $groupEntity = $this->getGroupByName($group);

                    if ($groupEntity) {
                        $user->addGroup($groupEntity);
                    }
                }
            }

            $this->insert($user);
        }

        return $this;
    }

    public function createGroupIfNotExists($role, $desciption = ''): static
    {
        if (!$this->groupRepo->findOneByRole($role)) {
            $group = new Group();
            $group
                ->setRole($role)
                ->setDescription($desciption)
            ;

            $this->em->persist($group);
            $this->em->flush();
        }

        return $this;
    }

    public function update(UserEntity $user): static
    {
        $this->em->persist($user);
        $this->em->flush();

        return $this;
    }

    public function delete(UserEntity $user): static
    {
        $this->em->remove($user);
        $this->em->flush();

        return $this;
    }

    public function encodePassword($user, $password): string
    {
        return $this->encoder->hashPassword($user, trim($password));
    }

    public function getUserFromSession(): ?\Symfony\Component\Security\Core\User\UserInterface
    {
        return $this->security->getUser();
    }

    public function getUsernameFromSession(): mixed
    {
        $username = '';
        $user = $this->getUserFromSession();

        if ($user) {
            $username = $user->getUsername();
        }

        return $username;
    }

    public function checkMetadataAccess($metadata, $user = null, $ignoreRole = null): bool
    {
        if (is_null($user)) {
            $user = $this->getUserFromSession();
        }

        if (!is_object($user)) {
            return false;
        }

        // OWNER HAS ACCESS
        if ($metadata->getInsertUser()->getId() === $user->getId()) {
            return true;
        }

        // todo: grant access with voter?
        return false;
    }

    public function generatePassword($length = 40): string
    {
        $password = "";
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
        srand((double)microtime()*1000000);

        for ($i = 0; $i < $length; $i++) {
            $password .= substr($chars, rand() % strlen($chars), 1);
        }

        return $password;
    }

    public function getRoles(): array
    {
        /** @var User $user */
        $user = $this->getUserFromSession();

        $roles = [];

        foreach ($user->getRoles() as $role) {
            if (!str_contains($role, 'ROLE_SYSTEM_')) {
                $roles[] = $role;
            }
        }

        return $roles;
    }
}
