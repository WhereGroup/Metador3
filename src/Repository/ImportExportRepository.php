<?php

namespace App\Repository;

use App\Entity\ImportExport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportExport>
 */
class ImportExportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportExport::class);
    }

    public function updateDateTime(ImportExport $entity): void
    {
        $date = \DateTime::createFromFormat('U.u', (string) microtime(true));

        $entity->setLastRun($date ?: new \DateTime());

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function countBySource(string $source): int
    {
        try {
            return (int) $this
                ->createQueryBuilder('u')
                ->select('count(u.id)')
                ->where('u.source = :source')
                ->setParameter('source', $source)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException) {
            return 0;
        }
    }

    public function deleteBySource(string $source): static
    {
        $this
            ->createQueryBuilder('u')
            ->delete(ImportExport::class, 'u')
            ->where('u.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();

        return $this;
    }

    public function save(ImportExport $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(ImportExport $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }
}

