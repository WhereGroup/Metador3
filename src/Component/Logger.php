<?php

namespace App\Component;

use App\Entity\Log;
use App\Event\LoggingEvent;
use App\Service\UserInterface;
use DateTime;
use Exception;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use App\Entity\User;
use Symfony\Contracts\Translation\TranslatorInterface;

class Logger
{
    private $flashBag;
    private $eventDispatcher;
    private $userService;
    private $translator;

    public function __construct(
        FlashBagInterface $flashBag,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        UserInterface $userService
    ) {
        $this->flashBag            = $flashBag;
        $this->eventDispatcher     = $eventDispatcher;
        $this->translator          = $translator;
        $this->userService         = $userService;
    }

    public function __destruct()
    {
        unset(
            $this->flashbag,
            $this->eventDispatcher,
            $this->userService,
            $this->translator
        );
    }

    public function newLog()
    {
        return new Log();
    }

    public function set(Log $log, bool $flush = true): static
    {
        // Find user
        /** @var User $user */
        $user = $log->getUser();

        if (is_null($user) && is_null($log->getUsername())) {
            $log->setUsername($this->userService->getUsernameFromSession());
        } elseif (is_null($log->getUsername()) && $user instanceof User) {
            $log->setUsername($user->getUsername());
        }

        if (is_null($log->getDateTime())) {
            try {
                $dateTime = new DateTime();
            } catch (Exception $e) {
                $dateTime = null;
            }

            $log->setDateTime($dateTime);
        }

        // Translate message
        $log->setMessage($this->translator->trans($log->getMessage(), $log->getMessageParameter()));

        // Set flash message
        if ($log->isFlashMessage()) {
            $this->flashBag->add($log->getType(), $log->getMessage());
        }

        // Throw event
        $this->eventDispatcher->dispatch(new LoggingEvent($log, $flush), 'metador.log');

        return $this;
    }

    public function error($message, $param = []): void
    {
        $this->log($message, $param, 'error');
    }

    protected function log($message, $param = [], $type = 'error'): void
    {
        $log = $this->newLog();
        $log
            ->setMessage($message, $param)
            ->setCategory('system')
            ->setType($type);
        $this->set($log);
    }
}
