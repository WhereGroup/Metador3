<?php

namespace App\Service\ApiRecords;

use App\Component\ApiRecords\Capabilities;
use App\Component\ApiRecords\OgcApiCqlJsonFilterReader;
use App\Component\ApiRecords\OgcApiException;
use App\Component\ApiRecords\Query;
use App\Component\ApiRecords\RecordMapper;
use App\Entity\Records as RecordsApiEntity;
use App\Repository\RecordsRepository;
use App\Service\Logger;
use App\Service\Metadata;
use App\Service\Plugin;
use App\Service\Search\DatabaseSearch;
use App\Service\Search\Expression;
use App\Service\Search\ExprHandlerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

class Records
{
    private $repo = null;
    private $kernel;
    private $templating;
    private $logger;
    private $plugin;
    private $metadataSearch;
    private $metadata;
    private $authorizationChecker;
    private $dbSearch;

    public function __construct(
        KernelInterface               $kernel,
        EntityManagerInterface        $em,
        Environment                   $templating,
        Logger                        $logger,
        Plugin                        $plugin,
        DatabaseSearch                $metadataSearch,
        Metadata                      $metadata,
        AuthorizationCheckerInterface $authorizationChecker,
        DatabaseSearch                $dbSearch,
        RecordsRepository             $repo,
    )
    {
        $this->kernel = $kernel;
        $this->repo = $repo;
        $this->templating = $templating;
        $this->logger = $logger;
        $this->plugin = $plugin;
        $this->metadataSearch = $metadataSearch;
        $this->metadata = $metadata;
        $this->authorizationChecker = $authorizationChecker;
        $this->dbSearch = $dbSearch;
//        $a = $this->metadataSearch === $this->dbSearch;
    }

    public function count(): int
    {
        return $this->repo->countAll();
    }

    public function all()
    {
        return $this->repo->findAll();
    }

    public function findOneBySlugAndSource($slug, $source)
    {
        return $this->repo->findOneBySlugAndSource($slug, $source);
    }

    public function save($entity): static
    {
        $this->repo->save($entity);
        return $this;
    }

    public function remove($entity): static
    {
        $this->repo->remove($entity);
        return $this;
    }

    /**
     * @throws OgcApiException
     */
    private function getExpressionForInstance(RecordsApiEntity $apiConf, ExprHandlerInterface $exprHandler): Expression
    {
        return OgcApiCqlJsonFilterReader::read($apiConf->getFilter(), $exprHandler);
    }

    private function mergeExpression(ExprHandlerInterface $exprHandler, Expression $exprA = null, Expression $exprB = null): ?Expression
    {
        if ($exprA === null && $exprB === null) {
            return null;
        } elseif ($exprA === null) {
            return $exprB;
        } elseif ($exprB === null) {
            return $exprA;
        } else {
            return new Expression(
                $exprHandler->andx([$exprA->getExpression(), $exprB->getExpression()]),
                array_merge($exprA->getParameters(), $exprB->getParameters())
            );
        }
    }

    /**
     * @throws OgcApiException
     */
    public function getRecordById(RecordsApiEntity $apiConf, string $id, array $queryables): array
    {
        /* @var ExprHandlerInterface $exprHandler */
        $exprHandler = $this->metadataSearch->createExpression();
        $instanceExpression =  $this->getExpressionForInstance($apiConf, $exprHandler);
        $queryExpression = OgcApiCqlJsonFilterReader::read(['op' => '=', 'args' => [['property' => 'id'], $id]], $exprHandler, $queryables);

        /* @var Expression $expr */
        $expr = $this->mergeExpression($exprHandler, $instanceExpression, $queryExpression);

        $result = $this->metadataSearch
            ->setSource($apiConf->getSource())
            ->setExpression($expr)
            ->find();

        return (new RecordMapper($this->metadataSearch))->mapRow($result['rows'][0]);
    }

    public function getRecords(RecordsApiEntity $apiConf, Query $query, Capabilities $capabilities): array
    {
        $metadataSearch = $this->dbSearch;
        /* @var ExprHandlerInterface $exprHandler */
        $exprHandler = $metadataSearch->createExpression();
        $instanceExpression =  $this->getExpressionForInstance($apiConf, $exprHandler);
        $queryExpression = $query->getFilter() ? OgcApiCqlJsonFilterReader::read($query->getFilter(), $exprHandler, $query->getQueryablesMap()) : null;
        /* @var Expression $expr */
        $expr = $this->mergeExpression($exprHandler, $instanceExpression, $queryExpression);

        $metadataSearch
            ->setSource($apiConf->getSource())
            ->setOffset($query->getOffset())
            ->setHits($query->getLimit());
        if ($query->getQ()) {
            $metadataSearch->setTerms($query->getQ());
        }

        if ($expr) {
            $metadataSearch->setExpression($expr);
        }

        $result = $metadataSearch->find();
        $rows = (new RecordMapper($this->metadataSearch))->mapRows($result['rows']);

        return $capabilities->generateFeatureCollection($query, $rows, ((array)$result['paging'])['count']);
    }
}
