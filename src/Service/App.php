<?php

namespace App\Service;

use Ramsey\Uuid\Uuid;
use App\Entity\User;

class App
{
    protected UserInterface $user;

    public function __construct(UserInterface $user)
    {
        $this->user = $user;
    }

    public function __destruct()
    {
        unset($this->user);
    }

    public function getUser($username): ?User
    {
        return is_null($username)
            ? $this->user->getUserFromSession()
            : $this->user->getByUsername($username);
    }

    public function generateUuid(): string
    {
        return (Uuid::uuid4())->toString();
    }
}
