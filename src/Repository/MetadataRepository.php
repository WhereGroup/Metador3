<?php

namespace App\Repository;

use App\Entity\Metadata;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Metadata>
 */
class MetadataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Metadata::class);
    }

    /**
     * @return array
     */
    public function getAllByProfile(string $profile): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.profile = :profile')
            ->setParameter('profile', $profile)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array|false
     */
    public function getDataObject(string $id): array|false
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.object AS object')
            ->where('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        if (isset($result[0]['object'])) {
            $object = $result[0]['object'];

            if (is_array($object)) {
                return $object;
            }
        }

        return false;
    }

    public function countBySource(string $source): int
    {
        return (int) $this
            ->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteBySource(string $source): static
    {
        $this->createQueryBuilder('u')
            ->delete(Metadata::class, 'u')
            ->where('u.source = :source')
            ->setParameter('source', $source)
            ->getQuery()
            ->execute();

        return $this;
    }

    /**
     * @return array
     */
    public function findByParams(object $finder): array
    {
        $qb = $this
            ->createQueryBuilder('m')
            ->select('m');

        $finder->getFilter($qb);

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array
     */
    public function findAndReturnObjects(int $offset, int $limit): array
    {
        return $this
            ->createQueryBuilder('m')
            ->select('m.object')
            ->orderBy('m.id')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function countAll(): int
    {
        return (int) $this
            ->createQueryBuilder('u')
            ->select('count(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array
     */
    public function countAndGroupBySources(): array
    {
        return $this
            ->createQueryBuilder('u')
            ->select('u.source, count(u)')
            ->groupBy('u.source')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    public function getChildren(string $parentUuid): array
    {
        return $this->findBy(['parent' => $parentUuid]);
    }

    public function truncate(?string $source = null): static
    {
        $rsm = new ResultSetMapping();

        if ($source !== null) {
            $rows = $this->createQueryBuilder('m')
                ->select('m.id')
                ->where('m.source = :source')
                ->setParameter('source', $source)
                ->getQuery()
                ->getResult();

            foreach ($rows as $row) {
                $this
                    ->getEntityManager()
                    ->getConnection()
                    ->executeStatement('DELETE FROM metadata_address WHERE metadata_id = ?', [$row['id']]);
                $this
                    ->getEntityManager()
                    ->getConnection()
                    ->executeStatement('DELETE FROM metadata_groups WHERE metadata_id = ?', [$row['id']]);
            }

            $this->createQueryBuilder('m')
                ->delete(Metadata::class, 'm')
                ->where('m.source = :source')
                ->setParameter('source', $source)
                ->getQuery()
                ->execute();

            return $this;
        }

        $this->createQueryBuilder('m')
            ->delete(Metadata::class, 'm')
            ->getQuery()
            ->execute();

        $this->getEntityManager()->getConnection()->executeStatement('DELETE FROM metadata_address');
        $this->getEntityManager()->getConnection()->executeStatement('DELETE FROM metadata_groups');

        return $this;
    }
}
