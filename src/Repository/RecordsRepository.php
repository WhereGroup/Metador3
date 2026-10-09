<?php

namespace App\Repository;

use App\Entity\Records;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Records>
 */
class RecordsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Records::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function countAll(): int
    {
        return (int) $this
            ->createQueryBuilder('u')
            ->select('count(u.slug)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countBySource(string $source): int
    {
        try {
            return (int) $this
                ->createQueryBuilder('u')
                ->select('count(u.slug)')
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
            ->delete(Records::class, 'u')
            ->where('u.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();

        return $this;
    }

    public function findOneBySlugAndSource(string $slug, string $source): ?Records
    {
        try {
            return $this
                ->createQueryBuilder('c')
                ->select('c')
                ->where('c.slug = :slug AND c.source = :source')
                ->setParameter('slug', $slug)
                ->setParameter('source', $source)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            return null;
        }
    }

    public function save(Records $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(Records $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }
}

