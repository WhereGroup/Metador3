<?php

namespace App\Service;

use App\Event\MetadataChangeEvent;
use App\Event\MetadataFlushEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class Database
 * @package App\Service
 */
class Database
{
    protected EntityManagerInterface $em;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $em, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function __destruct()
    {
        unset($this->em, $this->eventDispatcher);
    }

    public function persist($entity): static
    {
        $this->em->persist($entity);
        return $this;
    }

    public function delete($entity): static
    {
        $this->em->remove($entity);
        return $this;
    }

    public function getRepository($repo = \App\Entity\Metadata::class): EntityRepository
    {
        return $this->em->getRepository($repo);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    public function clearSqlObjectManager(): static
    {
        $this->em->clear();
        return $this;
    }

    public function flush(): static
    {
        $this->em->flush();
        return $this;
    }

    /**
     * In Doctrine DBAL 3+ wurde setSQLLogger() entfernt.
     * SQL-Logging wird jetzt über die doctrine.yaml Konfiguration gesteuert:
     * doctrine.dbal.logging: false
     *
     * Diese Methode bleibt aus Kompatibilitätsgründen erhalten.
     * @deprecated Verwende stattdessen die Konfiguration in doctrine.yaml
     */
    public function disableSqlLogger(): static
    {
        // In DBAL 3+ nicht mehr programmatisch möglich
        // Logging wird über doctrine.yaml gesteuert
        return $this;
    }

    public function dispatchFlush(): static
    {
        $this->eventDispatcher->dispatch(new MetadataFlushEvent(), 'metadata.flush');
        return $this;
    }

    public function dispatchPostTruncate(MetadataChangeEvent $event): static
    {
        $this->eventDispatcher->dispatch($event, 'metadata.post_truncate');
        return $this;
    }

    public function dispatchPreSave(MetadataChangeEvent $event): static
    {
        $this->eventDispatcher->dispatch($event, 'metadata.pre_save');
        return $this;
    }

    public function dispatchPostSave(MetadataChangeEvent $event): static
    {
        $this->eventDispatcher->dispatch($event, 'metadata.post_save');
        return $this;
    }

    public function dispatchPreDelete(MetadataChangeEvent $event): static
    {
        $this->eventDispatcher->dispatch($event, 'metadata.pre_delete');
        return $this;
    }

    public function beginTransaction(): static
    {
        $this->em->beginTransaction();
        return $this;
    }

    public function transactionCommit(): static
    {
        $this->em->commit();
        return $this;
    }

    public function transactionRollBack(): static
    {
        $this->em->rollBack();
        $this->em->clear();
        return $this;
    }
}
