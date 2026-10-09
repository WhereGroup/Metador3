<?php

namespace App\Repository;

use App\Entity\Csw;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Csw>
 */
class CswRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Csw::class);
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
            ->delete(Csw::class, 'u')
            ->where('u.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();

        return $this;
    }

    public function findOneBySlugAndSource(string $slug, string $source): ?Csw
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

    public function save(Csw $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(Csw $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }
}

