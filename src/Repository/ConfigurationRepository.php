<?php

namespace App\Repository;

use App\Entity\Configuration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Configuration>
 */
class ConfigurationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Configuration::class);
    }

    public function all(?string $filterType = null, ?string $filterValue = null): array
    {
        $qb = $this->createQueryBuilder('c')->select('c');

        if ($filterType !== null) {
            $qb->andWhere('c.filterType = :filterType')->setParameter('filterType', $filterType);
        }

        if ($filterValue !== null) {
            $qb->andWhere('c.filterValue = :filterValue')->setParameter('filterValue', $filterValue);
        }

        $rows = $qb->orderBy('c.filterType', 'ASC')->getQuery()->getArrayResult();

        for ($i = 0; $i < count($rows); $i++) {
            if ((bool) $rows[$i]['json'] === true) {
                $rows[$i]['value'] = json_decode($rows[$i]['value'], true);
            }

            unset($rows[$i]['json']);
        }

        return $rows;
    }

    public function removeAll(?string $filterType = null, ?string $filterValue = null): int
    {
        $qb = $this->createQueryBuilder('c')->delete(Configuration::class, 'c');

        if ($filterType !== null) {
            $qb->andWhere('c.filterType = :filterType')->setParameter('filterType', $filterType);
        }

        if ($filterValue !== null) {
            $qb->andWhere('c.filterValue = :filterValue')->setParameter('filterValue', $filterValue);
        }

        return $qb->getQuery()->execute();
    }

    public function get(string $key, ?string $filterType = null, ?string $filterValue = null, mixed $default = null): mixed
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c')
            ->where('c.key = :key')
            ->setParameter('key', $key);

        if ($filterType !== null) {
            $qb->andWhere('c.filterType = :filterType')->setParameter('filterType', $filterType);
        }

        if ($filterValue !== null) {
            $qb->andWhere('c.filterValue = :filterValue')->setParameter('filterValue', $filterValue);
        }

        $result = $qb->getQuery()->getOneOrNullResult();

        return $result ?? $default;
    }

    public function remove(string $key, ?string $filterType = null, ?string $filterValue = null): void
    {
        $qb = $this->createQueryBuilder('c')
            ->delete(Configuration::class, 'c')
            ->where('c.key = :key')
            ->setParameter('key', $key);

        if ($filterType !== null) {
            $qb->andWhere('c.filterType = :filterType')->setParameter('filterType', $filterType);
        }

        if ($filterValue !== null) {
            $qb->andWhere('c.filterValue = :filterValue')->setParameter('filterValue', $filterValue);
        }

        $qb->getQuery()->execute();
    }

    public function getValue(string $key, ?string $filterType = null, ?string $filterValue = null, mixed $default = null): mixed
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.value, c.json')
            ->where('c.key = :key')
            ->setParameter('key', $key);

        if ($filterType !== null) {
            $qb->andWhere('c.filterType = :filterType')->setParameter('filterType', $filterType);
        }

        if ($filterValue !== null) {
            $qb->andWhere('c.filterValue = :filterValue')->setParameter('filterValue', $filterValue);
        }

        $result = $qb->getQuery()->getScalarResult();

        if (!isset($result[0])) {
            return $default;
        }

        if ((bool) $result[0]['json'] === true) {
            return json_decode($result[0]['value'], true);
        }

        return $result[0]['value'];
    }

    public function set(string $key, mixed $value, string $filterType = '', string $filterValue = ''): void
    {
        /** @var Configuration|null $entity */
        $entity = $this->get($key, $filterType, $filterValue);

        if ($entity) {
            $entity->setValue($value);
        } else {
            $entity = new Configuration();
            $entity
                ->setKey($key)
                ->setValue($value)
                ->setFilterType($filterType)
                ->setFilterValue($filterValue);
        }

        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function truncate(): int
    {
        return $this->createQueryBuilder('c')
            ->delete(Configuration::class, 'c')
            ->getQuery()
            ->execute();
    }
}
