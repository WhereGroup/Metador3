<?php

namespace App\Repository;

use App\Entity\Log;
use App\Service\Search\Paging;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Log>
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    /**
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLogs(int $page = 1, int $limit = 20, array $params = []): array
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($params['type'])) {
            $qb->andWhere('p.type = :type')->setParameter('type', (string) $params['type']);
        }

        if (!empty($params['category'])) {
            $qb->andWhere('p.category = :category')->setParameter('category', (string) $params['category']);
        }

        if (!empty($params['subcategory'])) {
            $qb->andWhere('p.subcategory = :subcategory')->setParameter('subcategory', (string) $params['subcategory']);
        }

        if (!empty($params['operation'])) {
            $qb->andWhere('p.operation = :operation')->setParameter('operation', (string) $params['operation']);
        }

        if (!empty($params['username'])) {
            $qb->andWhere('p.username = :username')->setParameter('username', (string) $params['username']);
        }

        if (!empty($params['source'])) {
            $qb->andWhere('p.source = :source')->setParameter('source', (string) $params['source']);
        }

        $qb->select('count(p)');
        $count  = (int) $qb->getQuery()->getSingleScalarResult();
        $offset = ($page * $limit) - $limit;

        if ($offset > $count) {
            $offset = $count - $limit;
            $page   = (int) ceil($count / max($limit, 1));
        }

        if ($offset < 0) {
            $offset = 0;
            $page   = 1;
        }

        $paging = new Paging($count, $limit, $page);

        $qb->select('p');

        if (!empty($params['orderby']) && $params['orderby'] === 'datum') {
            $qb->orderBy('p.dateTime', 'ASC');
        } else {
            $qb->orderBy('p.id', 'DESC');
        }

        $result = $qb->setFirstResult($offset)->setMaxResults($limit)->getQuery()->getResult();

        return [
            'paging' => $paging->calculate(),
            'logs'   => $result,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function getOptions(string $column): array
    {
        $data = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT l.' . $column)
            ->from(Log::class, 'l')
            ->getQuery()->execute();

        $logs = [];

        foreach ($data as $row) {
            if (trim((string) $row[$column]) !== '') {
                $logs[] = $row[$column];
            }
        }

        return $logs;
    }

    public function truncate(): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQuery('DELETE FROM ' . Log::class)
            ->execute();
    }

    public function save(Log $entity, bool $flush = true): static
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }

    public function remove(Log $entity, bool $flush = true): static
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }

        return $this;
    }
}

