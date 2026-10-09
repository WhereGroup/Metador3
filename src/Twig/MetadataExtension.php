<?php

namespace App\Twig;

use App\Component\Exceptions\MetadorException;
use App\Service\User;
use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MetadataExtension extends AbstractExtension
{
    public function __construct(
        private readonly User $user
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('metadata_is_locked', $this->isLocked(...)),
        ];
    }

    /**
     * @throws MetadorException
     */
    public function isLocked(array $p): bool
    {
        if ((bool) $p['_locked'] === false) {
            return false;
        }

        if (!isset($p['_lock_user']) || !isset($p['_lock_time'])) {
            throw new MetadorException('_lock_user and _lock_time are missing in Dataobject!');
        }

        $dateTime = new DateTime();
        $timeout = ($dateTime->getTimestamp() - $p['_lock_time']) > (int) ini_get("session.gc_maxlifetime");

        if ($timeout || $p['_lock_user'] === $this->user->getUsernameFromSession()) {
            return false;
        }

        return true;
    }
}
