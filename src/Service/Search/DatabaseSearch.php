<?php

namespace App\Service\Search;

use App\Repository\MetadataRepository;
use App\Service\Metadata;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\QueryBuilder;

class DatabaseSearch extends Search implements SearchInterface
{
    protected ?EntityManagerInterface $em = null;
    protected ?QueryBuilder $qb = null;
    protected ?Expression $spatial = null;
    protected MetadataRepository $repo;

    public function __construct(EntityManagerInterface $em, Metadata $metadata, MetadataRepository $repo)
    {
        parent::__construct($metadata);
        $this->em = $em;
        $this->repo = $repo;
        $this->qb = $repo->createQueryBuilder('m');
    }

    protected function resetQuery(): void
    {
        $this->qb = $this->repo->createQueryBuilder('m');
    }

    public function setSpatialExpression(Expression $spatial): void
    {
        $this->spatial = $spatial;
    }

    public function find(): array
    {
        $this->prepareQuery();
        return [
            'paging' => $this->getResultPaging(),
            'rows'   => $this->getResult(),
            'facet'  => []
        ];
    }

    protected function prepareQuery(): void
    {
        if ($this->getGroups()) {
            $this->qb->leftJoin('m.groups', 'g');
        }

        // Searchterms
        if (trim($this->terms) !== "") {
            $termsStr = trim(preg_replace('/[\-\|\(\)]/', ' ', strtolower($this->terms)));
            $terms = (array)preg_split('/\s+/', trim($termsStr));
            if (count($terms) > 0) {
                $index = 0;
                foreach ($terms as $term) {
                    if ($term === "") {
                        continue;
                    }
                    $this->qb
                        ->andWhere('m.searchfield LIKE :termX'.$index)
                        ->setParameter('termX'.$index, "%".$term."%");
                    $index++;
                }
                unset($index);
            }
        }

        if ($this->spatial) {
            if ($this->spatial->getExpression() instanceof Andx) {
                foreach ($this->spatial->getExpression()->getParts() as $part) {
                    $this->qb->andWhere($part);
                }
            } else {
                $this->qb->andWhere($this->spatial->getExpression());
            }
            foreach ($this->spatial->getParameters() as $key => $val) {
                $this->qb->setParameter($key, $val);
            }
        }

        if ($this->expression) {
            if ($this->expression->getExpression() instanceof Andx) {
                foreach ($this->expression->getExpression()->getParts() as $part) {
                    $this->qb->andWhere($part);
                }
            } else {
                $this->qb->andWhere($this->expression->getExpression());
            }
            foreach ($this->expression->getParameters() as $key => $val) {
                $this->qb->setParameter($key, $val);
            }
        }

        if (!empty($this->getSource())) {
            $this->qb->andWhere('m.source = :sourceX')->setParameter('sourceX', $this->getSource());
        }

        if (!is_null($this->public)) {
            $this->qb->andWhere('m.public = :public')->setParameter('public', (boolean)$this->public);
        }

        if (!empty($this->getProfile())) {
            if (is_array($this->getProfile())) {
                $this->qb->andWhere($this->qb->expr()->in('m.profile', $this->getProfile()));
            } elseif (is_string($this->getProfile())) {
                $this->qb->andWhere('m.profile = :profileX')->setParameter('profileX', $this->getProfile());
            }
        }

        if (!empty($this->getKeyword())) {
            if (is_array($this->getKeyword())) {
                $index = 0;
                foreach ($this->getKeyword() as $keyword) {
                    $this->qb
                        ->andWhere('m.keywords LIKE :keywordX'.$index)
                        ->setParameter('keywordX'.$index, "%".strtolower($keyword)."%");
                }
                unset($index);
            } elseif (is_string($this->getKeyword())) {
                $this->qb
                    ->andWhere('m.keywords LIKE :keywordX')
                    ->setParameter('keywordX', '%' . strtolower($this->getKeyword()) . '%');
            }
        }
    }

    public function getResult(): array
    {
        $this->qb->select('m.object');

        if (!is_null($this->offset)) {
            $this->qb->setFirstResult($this->offset);
        }

        if (!is_null($this->hits)) {
            $this->qb->setMaxResults($this->hits);
        }

        if (!empty($this->getSort())) {
            if (is_string($this->getSort()) && $this->tableExists($this->getSort())) {
                $this->qb->orderBy('m.' . $this->getSort());
            } elseif (is_array($this->getSort())) {
                foreach ($this->getSort() as $sort) {
                    if ($this->tableExists($sort)) {
                        $this->qb->addOrderBy('m.' . $sort);
                    }
                }
            }
        }

        $result = $this->qb->getQuery()->getArrayResult();

        return is_array($result) ? $result : [];
    }

    protected function tableExists($table): bool
    {
        return in_array($table, ['title', 'date', 'hierarchyLevel', 'dateStamp']);
    }

    public function getResultCount()
    {
        return $this->qb
            ->select('count(m)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function createExpression(): DatabaseExprHandler
    {
        return new DatabaseExprHandler();
    }
}
