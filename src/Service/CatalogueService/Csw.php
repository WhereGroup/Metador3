<?php

namespace App\Service\CatalogueService;

use App\Component\CatalogueService\CswException;
use App\Component\CatalogueService\DescribeRecord;
use App\Component\CatalogueService\Exception\GetCapabilitiesNotFoundException;
use App\Component\CatalogueService\GetCapabilities;
use App\Component\CatalogueService\GetRecordById;
use App\Component\CatalogueService\GetRecords;
use App\Component\CatalogueService\Parameter\GetParameter;
use App\Component\CatalogueService\Parameter\Parameter;
use App\Component\CatalogueService\Parameter\PostDomParameter;
use App\Component\CatalogueService\Parameter\TransactionParameter;
use App\Component\CatalogueService\Transaction;
use App\Component\CatalogueService\TransactionOperation;
use App\Component\Exceptions\MetadataNotFoundException;
use App\Component\PDFExport;
use App\Component\Utils\ArrayParser;
use App\Component\Utils\Stopwatch;
use App\Repository\CswRepository;
use App\Service\Logger;
use App\Service\Metadata;
use App\Service\Plugin;
use App\Service\Search\DatabaseSearch;
use App\Service\Search\Expression;
use App\Service\Search\ExprHandlerInterface;
use App\Service\Search\JsonFilterReader;
use App\Service\Search\Search;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use DOMDocument;
use DOMElement;
use Exception;
use App\Entity\Csw as CswEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

class Csw
{
    private $repo = null;
    private $templating;
    private $logger;
    private $plugin;
    private $metadataSearch;
    private $metadata;
    private $authorizationChecker;

    public function __construct(
        Environment $templating,
        Logger $logger,
        Plugin $plugin,
        DatabaseSearch $metadataSearch,
        Metadata $metadata,
        AuthorizationCheckerInterface $authorizationChecker,
        CswRepository $repo,
    ) {
        $this->repo = $repo;
        $this->templating = $templating;
        $this->logger = $logger;
        $this->plugin = $plugin;
        $this->metadataSearch = $metadataSearch;
        $this->metadata = $metadata;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function count(): int
    {
        return (int)$this->repo->countAll();
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

    public function readGetParameter(array $getParameters): GetParameter
    {
        return new GetParameter($getParameters);
    }

    public function readPostParameter($content): PostDomParameter
    {
        return new PostDomParameter($content);
    }

    public function readTransactionParameter($content): TransactionParameter
    {
        return new TransactionParameter($content);
    }

    public function getCapabilities(Parameter $parameter, CswEntity $cswConfig, $url, $urlTransaction): string
    {
        $operation = $parameter->initOperation(new GetCapabilities($cswConfig, $url, $urlTransaction));
        $operation->validateParameter();

        return $this->templating->render(
            'catalogueService/CSW/getcapabilities_response.xml.twig',
            [
                'getcap' => $operation,
            ]
        );
    }

    public function describeRecord(Parameter $parameter, CswEntity $cswConfig)
    {
        $operation = $parameter->initOperation(new DescribeRecord($cswConfig));
        $operation->validateParameter();

        return $this->templating->render(
            'catalogueService/CSW/describerecord.xml.twig',
            ['descrec' => $operation]
        );
    }

    public function getRecordById(Parameter $parameter, CswEntity $cswConfig)
    {
        $exprHandler = $this->metadataSearch->createExpression();
        $operation = $parameter->initOperation(new GetRecordById($cswConfig, $exprHandler));
        $operation->validateParameter();

        /* @var Expression $cswAndGetRecordByIdExpr */
        $cswAndGetRecordByIdExpr = $this->mergeExpression(
            $exprHandler,
            $this->getExpressionForCsw($cswConfig, $exprHandler),
            $operation->getConstraint()
        );

        // Prepare result for full, summary and brief metadata.
        $result = $this->metadataSearch
            ->setSource($cswConfig->getSource())
            ->setExpression($cswAndGetRecordByIdExpr)
            ->find();

        if ($cswConfig->getSeriesAsDataset()) {
            $result['rows'] = $this->replaceSeries($result['rows']);
        }
        // Prepare result for full, summary and brief metadata.
        $result['rows'] = $this->prepareResult($result['rows'], $operation->getElementSetName());

        switch (strtolower($operation->getOutputFormat())) {
            case 'application/pdf':
                $this->dumpPDF($result);
                break;
            case 'text/html':
                $p = $this->getFirstMetadata($result);

                return new Response($this->templating->render(
                    $this->plugin->getPluginTemplatePath($p['_profile']) . '/Export/html.html.twig',
                    ["p" => $p]
                ));
                break;
            default:
                return new Response($this->templating->render(
                    'catalogueService/CSW/recordbyid_response.xml.twig',
                    [
                        'getredcordbyid' => $operation,
                        'templates'      => $this->getProfileLocations(),
                        'records'        => $result['rows'],
                    ]
                ), Response::HTTP_OK, ['content-type' => 'application/xml; charset=utf-8']);
        }
    }


    public function getRecords(Parameter $parameter, CswEntity $cswConfig)
    {
        /* @var ExprHandlerInterface $exprHandler */
        $exprHandler = $this->metadataSearch->createExpression();

        /**
         * @var GetRecords $operation
         */
        $operation = $parameter->initOperation(new GetRecords($cswConfig, $exprHandler, $cswConfig->getSeriesAsDataset()));
        $operation->validateParameter();

        /* @var Expression $cswAndGetRecordsExpr */
        $cswAndGetRecordsExpr = $this->mergeExpression(
            $exprHandler,
            $this->getExpressionForCsw($cswConfig, $exprHandler),
            $operation->getConstraint()
        );

        $offset = $operation->getStartPosition() - 1;
        $this->metadataSearch
            ->setHits($operation->getMaxRecords())
            ->setOffset($offset)
            ->setSource($cswConfig->getSource());
        if ($cswAndGetRecordsExpr) {
            $this->metadataSearch->setExpression($cswAndGetRecordsExpr);
        }

        // Prepare result for full, summary and brief metadata.
        $result  = $this->metadataSearch->find();
        if ($cswConfig->getSeriesAsDataset()) {
            $result['rows'] = $this->replaceSeries($result['rows']);
        }
        $records = $this->prepareResult($result['rows'], $operation->getElementSetName());
        $matched = $result['paging']->count;
        $next = $offset + count($records) + 1;

        switch (strtolower($operation->getOutputFormat())) {
            case 'application/pdf':
                $this->dumpPDF($result);
                break;
            case 'text/html':
                $p = $this->getFirstMetadata($result);

                return new Response($this->templating->render(
                    $this->plugin->getPluginTemplatePath($p['_profile']) . '/Export/pdf.html.twig',
                    ["p" => $p]
                ));
                break;
            default:
                return new Response($this->templating->render(
                    'catalogueService/CSW/records_response.xml.twig',
                    [
                        'getrecords' => $operation,
                        'templates'  => $this->getProfileLocations(),
                        'timestamp'  => (new DateTime())->format('Y-m-d\TH:i:s'),
                        'matched'    => $matched,
                        'records'    => $records,
                        'nextrecord' => $next > $matched ? 0 : $next,
                    ]
                ), Response::HTTP_OK, ['content-type' => 'application/xml; charset=utf-8']);
        }
    }

    private function replaceSeries(array $rows)
    {
        $l = count($rows);
        for ($i = 0; $i < $l; $i++) {
            if (!isset($rows[$i]['object'])) {
                continue;
            }
            $hierarchyLevel = ArrayParser::get($rows[$i]['object'], 'hierarchyLevel', '');
            if ($hierarchyLevel === "series") {
                ArrayParser::set($rows[$i]['object'], 'hierarchyLevel', "dataset");
            }
        }
        return $rows;
    }

    private function prepareResult(array $rows, $elementSetName = 'full')
    {
        if ($elementSetName != 'summary' && $elementSetName != 'brief') {
            return $rows;
        }

        $newResult = [];

        foreach ($rows as $row) {
            if (!isset($row['object'])) {
                continue;
            }

            $p = $row['object'];
            // brief
            $newObject['object'] = [
                'fileIdentifier' => ArrayParser::get($p, 'fileIdentifier', ''),
                'hierarchyLevel' => ArrayParser::get($p, 'hierarchyLevel', ''),
                'title'          => ArrayParser::get($p, 'title', ''),
                'bbox'           => ArrayParser::get($p, 'bbox', []),
            ];

            $this->metadata->mergeSystemInformations($p, $newObject['object']);

            if ($elementSetName === 'brief') {
//                $newObject['object'] = json_encode($newObject['object']);
                $newResult[] = $newObject;
                continue;
            }

            // summary
            $newObject['object']['dateStamp'] = ArrayParser::get($p, 'dateStamp', '');
            $newObject['object']['abstract'] = ArrayParser::get($p, 'abstract', '');

//            $newObject['object'] = json_encode($newObject['object']);
            $newResult[] = $newObject;
        }

        return $newResult;
    }

    public function transaction(TransactionParameter $parameter, CswEntity $cswConfig): string
    {
        $operationName = $parameter->getOperationName();

        if ($operationName === 'GetCapabilities') {
            throw new GetCapabilitiesNotFoundException();
        } elseif ($operationName !== 'Transaction') {
            throw new CswException('request', CswException::OPERATIONNOTSUPPORTED);
        }

        /** @var Transaction $operation */
        $operation = $parameter->initOperation(new Transaction($cswConfig));

        while (($action = $parameter->nextAction($operation, $this->metadataSearch->createExpression()))) {
            switch (($atype = $action->getType())) {
                case Transaction::INSERT:
                    if (!$cswConfig->getInsert()) {
                        throw new CswException($atype, CswException::OPERATIONNOTSUPPORTED);
                    }
                    $operation->addInserted($this->doInsert($cswConfig, $action, $parameter));
                    break;
                case Transaction::UPDATE:
                    if (!$cswConfig->getUpdate()) {
                        throw new CswException($atype, CswException::OPERATIONNOTSUPPORTED);
                    }
                    $operation->addUpdated($this->doUpdate($cswConfig, $action, $parameter));
                    break;
                case Transaction::DELETE:
                    if (!$cswConfig->getDelete()) {
                        throw new CswException($atype, CswException::OPERATIONNOTSUPPORTED);
                    }
                    $operation->addDeleted($this->doDelete($cswConfig, $action, $parameter));
                    break;
                default:
                    throw new CswException($atype, CswException::OPERATIONNOTSUPPORTED);
            }
        }

        return $this->templating->render(
            'catalogueService/CSW/transaction_response.xml.twig',
            ['ta' => $operation]
        );
    }

    public function doInsert(CswEntity $cswConfig, TransactionOperation $action, TransactionParameter $handler): int
    {
        gc_collect_cycles();
        $this->metadata->db->clearSqlObjectManager()->disableSqlLogger();

        $inserted = 0;

        /* list of metadates- no filter */
        foreach ($action->getItems() as $mdMetadata) {
            $hierarchyLevel = $handler->valueFor('./gmd:hierarchyLevel[1]/gmd:MD_ScopeCode/@codeListValue', $mdMetadata);
            $profiles = $cswConfig->getProfileMapping();

            if (isset($profiles[$hierarchyLevel])) {
                $p = $this->metadata
                    ->getProcessor()
                    ->xmlToObjectByProfile(self::elementToString($mdMetadata), $profiles[$hierarchyLevel]);
                try {
                    $entity = $this->metadata->findById($p['_uuid']);
                    if ($entity) {
                        throw new CswException('fileIdentifier', CswException::INVALIDPARAMETERVALUE);
                    }
                    unset($entity);
                } catch (MetadataNotFoundException $e) {
                }

                $this->metadata->insertByObject($p, [
                    'source'   => $cswConfig->getSource(),
                    'profile'  => $profiles[$hierarchyLevel],
                    'username' => $cswConfig->getUsername(),
                    'public'   => true
                ]);

                $inserted++;
                continue;
            }

            $this->log($cswConfig, 'warning', 'insert', '', 'Type: $hl ist nicht unterstützt');
        }

        return $inserted;
    }

    public function doUpdate(CswEntity $cswConfig, TransactionOperation $action, TransactionParameter $handler): int
    {
        $sw = new Stopwatch();
        gc_collect_cycles();
        $this->metadata->db->clearSqlObjectManager()->disableSqlLogger();

        $updated = 0;
        if ($action->getConstraint()) {
            /* @var ExprHandlerInterface $exprHandler */
            $exprHandler = $this->metadataSearch->createExpression();

            /* @var Expression $cswAndDeleteExpr */
            $cswAndDeleteExpr = $this->mergeExpression(
                $exprHandler,
                $this->getExpressionForCsw($cswConfig, $exprHandler),
                $action->getConstraint()
            );

            $this->metadataSearch->setSource($cswConfig->getSource());

            if ($cswAndDeleteExpr) {
                $this->metadataSearch->setExpression($cswAndDeleteExpr);
            }

            $records = $this->metadataSearch->find();

            if ($records['rows'] === false) {
                return $updated;
            }

            foreach ($records['rows'] as $record) {
                $existing = $record['object'];

                foreach ($action->getItems() as $mdMetadata) {
                    $hl = $handler->valueFor('./gmd:hierarchyLevel[1]/gmd:MD_ScopeCode/text()', $mdMetadata);
                    $profiles = $cswConfig->getProfileMapping();

                    if (isset($profiles[$hl])) {
                        $new = $this->metadata->getProcessor()->xmlToObjectByProfile(self::elementToString($mdMetadata), $profiles[$hl]);
                        $id = !empty($existing['_uuid']) ? $existing['_uuid'] : null;

                        $this->metadata->updateByObject($new, [
                            'source'   => $cswConfig->getSource(),
                            'profile'  => $profiles[$hl],
                            'username' => $cswConfig->getUsername(),
                            'public'   => true
                        ]);

                        $updated++;
                        continue;
                    }

                    $this->log($cswConfig, 'warning', 'update', '', 'Type: $hl ist nicht unterstützt');
                    break;
                }

                unset($existing);
            }

            unset($records);
        }

        $sw->stop();

        $this->log($cswConfig, 'debug', 'update', '', 'Updated ' . $updated . ' metadata in '
            . $sw->duration() . ' seconds.');

        return $updated;
    }

    public function doDelete(CswEntity $cswConfig, TransactionOperation $action, TransactionParameter $handler): int
    {
        $this->metadata->db->clearSqlObjectManager()->disableSqlLogger();

        $deleted = 0;
        if ($action->getConstraint()) {
            /* @var ExprHandlerInterface $exprHandler */
            $exprHandler = $this->metadataSearch->createExpression();

            /* @var Expression $cswAndDeleteExpr */
            $cswAndDeleteExpr = $this->mergeExpression(
                $exprHandler,
                $this->getExpressionForCsw($cswConfig, $exprHandler),
                $action->getConstraint()
            );

            $this->metadataSearch->setSource($cswConfig->getSource());

            if ($cswAndDeleteExpr) {
                $this->metadataSearch->setExpression($cswAndDeleteExpr);
            }

            $records = $this->metadataSearch->find();

            if ($records['rows'] !== false) {
                foreach ($records['rows'] as $record) {
                    $p = $record['object'];

                    $entity = $this->metadata->findById($p['_uuid']);

                    if ($entity) {
                        $this->metadata->delete($entity);
                    }

                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    private function getExpressionForCsw(CswEntity $cswConfig, ExprHandlerInterface $exprHandler): ?Expression
    {
        return JsonFilterReader::read($cswConfig->getFilter(), $exprHandler);
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

    private function getProfileLocations(): array
    {
        $templates = [];
        $profiles = $this->plugin->getActiveProfiles();

        if (empty($profiles)) {
            return $templates;
        }

        foreach ($profiles as $key => $profile) {
            $templates[$key] = $profile['template_path'] . '/Export/metadata.xml.twig';
        }

        return $templates;
    }

    private function log(CswEntity $entity, $type, $operation, $identifier, $message): void
    {
        $log = $this->logger->newLog();
        $log
            ->setType($type)
            ->setCategory('application')
            ->setSubcategory('csw')
            ->setOperation($operation)
            ->setSource($entity->getSource())
            ->setIdentifier($identifier)
            ->setMessage($message)
            ->setUser($entity->getUsername());

        $this->logger->set($log);
    }

    private static function elementToString(DOMElement $element): false|string
    {
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($element, true));
        return $doc->saveXML();
    }

    private function getFirstMetadata($result)
    {
        if (!isset($result['rows'][0])) {
            throw new Exception("Metadatensatz nicht gefunden");
        }

        $p = $result['rows'][0]['object'];

        if (!$this->authorizationChecker->isGranted('view', $p)) {
            throw new AccessDeniedException('Unable to access this metadata!');
        }

        return $p;
    }

    private function dumpPDF(array $result): void
    {
        $p = $this->getFirstMetadata($result);
        error_reporting(E_ERROR);
        $pdf = new PDFExport('P', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->setUUID($p['_uuid']);
        $pdf->createPdf($this->templating->render(
            $this->plugin->getPluginTemplatePath($p['_profile']) . '/Export/pdf.html.twig',
            [
                "p" => $p,
                "outputFormat" => "pdf"
            ]
        ), $p);
    }
}
