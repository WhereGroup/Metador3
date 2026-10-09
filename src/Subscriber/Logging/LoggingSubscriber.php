<?php

namespace App\Subscriber\Logging;

use App\Service\Plugin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use App\Entity\Log;
use App\Event\LoggingEvent;
use App\Service\Configuration;

/**
 * Class LoggingSubscriber
 * @package App\Subscriber\Logging
 */
class LoggingSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Configuration $configuration,
        private readonly Plugin $plugin
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'metador.log' => ['onMetadorLog', 0],
        ];
    }

    /**
     * @param LoggingEvent $event
     */
    public function onMetadorLog(LoggingEvent $event): void
    {
        if (!$this->plugin->isActive('metador_logging')) {
            return;
        }

        $log = $event->getLog();

        $systemLogLevel = strtolower($this->configuration->get('log_level', 'plugin', 'metador_logging'));

        if (($systemLogLevel === Log::INFO && !in_array($log->getType(), [
                Log::INFO, Log::SUCCESS, Log::WARNING, Log::ERROR
            ])) ||
            ($systemLogLevel === Log::WARNING && !in_array($log->getType(), [Log::WARNING, Log::ERROR])) ||
            ($systemLogLevel === Log::ERROR && $log->getType() != Log::ERROR)
        ) {
            return;
        }

        $entity = new Log();

        $entity
            ->setType($log->getType())
            ->setMessage($log->getMessage())
            ->setUsername($log->getUsername())
            ->setCategory($log->getCategory())
            ->setSubcategory($log->getSubcategory())
            ->setOperation($log->getOperation())
            ->setSource($log->getSource())
            ->setIdentifier($log->getIdentifier())
            ->setDateTime($log->getDateTime())
            ->setPath($log->getPath())
            ->setParams($log->getParams())
        ;

        $this->em->persist($entity);

        if ($event->getFlush()) {
            $this->em->flush();
        }
    }
}

