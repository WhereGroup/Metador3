<?php

namespace App\Repository;

use App\Entity\Address;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 */
class AddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function save(Address $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(Address $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function countAll(): int
    {
        return (int) $this
            ->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function search(string $terms, int $page = 1, int $hits = 10, bool $countOnly = false): array|int
    {
        $qb = $this->createQueryBuilder('u');

        if ($countOnly === false) {
            $qb
                ->select('u')
                ->setFirstResult(($page * $hits) - $hits)
                ->setMaxResults($hits);
        } else {
            $qb->select('count(u)');
        }

        $orx = $qb->expr()->orX();

        foreach (explode(' ', strtolower($terms)) as $word) {
            $orx->add(
                $qb->expr()->like('u.searchfield', $qb->expr()->literal('%' . $word . '%'))
            );
        }

        $qb->where($orx);

        if ($countOnly === false) {
            return $qb->getQuery()->getArrayResult();
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function truncate(): int
    {
        return $this->createQueryBuilder('u')
            ->delete(Address::class, 'u')
            ->getQuery()
            ->execute();
    }
}
