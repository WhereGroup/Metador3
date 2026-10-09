<?php

namespace App\Repository;

use App\Entity\Wms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wms>
 *
 * @method Wms|null find($id, $lockMode = null, $lockVersion = null)
 * @method Wms|null findOneBy(array $criteria, array $orderBy = null)
 * @method Wms[]    findAll()
 * @method Wms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wms::class);
    }

    public function save(Wms $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(Wms $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    /**
     * Get all WMS entries ordered by priority
     *
     * @return array<int, array{url: string|null, title: string|null, format: string|null, layers: array, version: string|null, visible: bool, opacity: float}>
     */
    public function all(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.gmUrl as url, w.title, w.format, w.layers, w.version, w.visible, w.opacity')
            ->orderBy('w.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all WMS entries ordered by priority
     *
     * @return Wms[]
     */
    public function findAllOrderedByPriority(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.priority', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
