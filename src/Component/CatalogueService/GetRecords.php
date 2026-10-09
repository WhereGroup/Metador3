<?php

namespace App\Component\CatalogueService;

use App\Component\CatalogueService\Search\GmlFilterReader;
use App\Entity\Csw;
use App\Service\Search\ExprHandler;
use App\Service\Search\PropertyNameNotFoundException;

class GetRecords extends FindRecord
{

    protected static array $parameterMap = [
        '/csw:GetRecords/@version' => 'version',
        '/csw:GetRecords/@service' => 'service',
        '/csw:GetRecords/@outputSchema' => 'outputSchema',
        '/csw:GetRecords/@outputFormat' => 'outputFormat',
        '/csw:GetRecords/@resultType' => 'resultType',
        '/csw:GetRecords/@startPosition' => 'startPosition',
        '/csw:GetRecords/@maxRecords' => 'maxRecords',
        '/csw:GetRecords/csw:Query/@typeNames' => 'typeNames',
        '/csw:GetRecords/csw:Query/csw:ElementSetName/text()' => 'elementSetName',
        '/csw:GetRecords/csw:Query/csw:ElementName/text()' => 'elementName', # multiple?
        '/csw:GetRecords/csw:Query/csw:Constraint/ogc:Filter' => 'Constraint',
        '/csw:GetRecords/csw:Query/ogc:SortBy' => 'sortBy',
        '/csw:GetRecords/csw:RequestId/text()' => 'requestId',
    ];

    /* @var array supported typenames */
    protected static $TYPENAMES = ['gmd:MD_Metadata'];

    /* Request parameters */
    protected $typeNames;
    protected $startPosition;
    protected $maxRecords;
    protected $sortBy;
    protected $constraintLanguage;
    protected $resultType;
    protected $requestId;
    protected $namespace;
    protected $responseHandler;
    protected $elementName;
    protected $deistributedSearch;
    protected $hopCount;
    /**
     * @var bool $seriesAsDataset
     */
    protected $seriesAsDataset;

    /**
     * {@inheritdoc}
     */
    public function __construct(Csw $entity, ExprHandler $exprHandler, $seriesAsDataset)
    {
        parent::__construct($entity, $exprHandler);
        $this->resultType = self::RESULTTYPE_HITS;
        $this->constraintLanguage = 'FILTER';
        $this->startPosition = 1;
        $this->maxRecords = 10;
        $this->sortBy = [];
        $this->supportedOutputFormats = ["application/xml", "text/html", "application/pdf"];
        $this->seriesAsDataset = $seriesAsDataset;
    }

    /**
     * {@inheritdoc}
     */
    public function getGETParameterMap(): array
    {
        return array_merge(['constraintLanguage'], array_values(self::$parameterMap));
    }

    /**
     * {@inheritdoc}
     */
    public function getPOSTParameterMap(): array
    {
        return self::$parameterMap;
    }

    public function getTypeNames()
    {
        return $this->typeNames;
    }

    public function setTypeNames($typeNames): static
    {
        $typeNameArr = preg_split('/[\s,]/', $typeNames);
        $this->isListAtList('typeNames', $typeNameArr, self::$TYPENAMES, false); # only check
        $this->typeNames = $typeNames;

        return $this;
    }

    public function getStartPosition(): int
    {
        return $this->startPosition;
    }

    public function setStartPosition($startPosition): static
    {
        if ($startPosition !== null) {
            $this->startPosition = self::getGreaterThan(
                'startPosition',
                self::getPositiveInteger('startPosition', $startPosition),
                0
            );
        }

        return $this;
    }

    public function getMaxRecords(): int
    {
        return $this->maxRecords;
    }

    public function setMaxRecords($maxRecords): static
    {
        if ($maxRecords !== null) {
            $this->maxRecords = self::getGreaterThan(
                'maxRecords',
                self::getPositiveInteger('maxRecords', $maxRecords),
                0
            );
        }

        return $this;
    }

    public function getSortBy(): ?array
    {
        return $this->sortBy;
    }

    public function setSortBy(?array $sortBy): static
    {
        $this->sortBy = is_array($sortBy) && count($sortBy) > 0 ? $sortBy : null;
        # @TODO split and check if items supported/exist
        return $this;
    }

    public function setConstraint($constraintContent)
    {
        // only xml Filter is supported TODO for other (e.g. CQL)
        try {
            if ($constraintContent !== null && is_string($constraintContent)) {
                $xml = '<?xml version="1.0" ?>'
                    .'<csw:Filter xmlns:csw="http://www.opengis.net/cat/csw/2.0.2"'
                    .' xmlns="http://www.opengis.net/cat/csw/2.0.2" xmlns:ogc="http://www.opengis.net/ogc">'
                    .$constraintContent.'</csw:Filter>';
                $dom = new \DOMDocument();
                if (!$dom->loadXML($xml, LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NOENT | LIBXML_XINCLUDE)) {
                    throw new CswException('filter', CswException::OPERATIONPARSINGFAILED);
                }
                $this->constraint = GmlFilterReader::readFromCsw($dom->documentElement, $this->exprHandler, $this->seriesAsDataset);
            } elseif ($constraintContent !== null && $constraintContent instanceof \DOMElement) {
                $this->constraint = GmlFilterReader::readFromCsw($constraintContent, $this->exprHandler, $this->seriesAsDataset);
            }

            return $this;
        } catch (PropertyNameNotFoundException $e) {
            $this->addCswException($e->getPropertyName(), CswException::INVALIDPARAMETERVALUE);
        } catch (CswException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->addCswException('Constraint', CswException::INVALIDPARAMETERVALUE);
        }
    }

    public function getConstraintLanguage()
    {
        return $this->constraintLanguage;
    }

    public function setConstraintLanguage($constraintLanguage)
    {
        self::isStringAtList('constraintLanguage', $constraintLanguage, [$this->constraintLanguage], false);

        return $this;
    }

    public function getResultType()
    {
        return $this->resultType;
    }

    public function setResultType($resultType): static
    {
        if (self::isStringAtList('resultType', $resultType, self::RESULTTYPE, false)) {
            $this->resultType = $resultType;
        }

        return $this;
    }

    public function getRequestId()
    {
        return $this->requestId;
    }

    public function setRequestId($requestId): static
    {
        $this->requestId = $requestId;

        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $value): void
    {
        switch ($name) {
            case 'resultType':
                $this->setResultType($value);
                break;
            case 'typeNames':
                $this->setTypeNames($value);
                break;
            case 'startPosition':
                $this->setStartPosition($value);
                break;
            case 'maxRecords':
                $this->setMaxRecords($value);
                break;
            case 'constraintLanguage':
                $this->setConstraintLanguage($value);
                break;
            case 'Constraint':
                $this->setConstraint($value);
                break;
            case 'sortBy':
                $this->setSortBy($value);
                break;
            case 'requestId':
                $this->setRequestId($value);
                break;
            case 'namespace':
                // not yet implemented
                break;
            case 'responseHandler':
//                  not yet implemented
                break;
            case 'ResponseHandler':
//                 not yet implemented
                break;
            case 'elementName':
                // @TODO if exists
                break;
            case 'distributedSearch':
//                not yet implemented
//                $this->deistributedSearch = $value; # check if $value is a boolean
                break;
            case 'hopCount':
//                not yet implemented
//                $this->hopCount = $value; #check if $value is a positive integer and deistributedSearch is requested.
                break;
            default:
                parent::setParameter($name, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateParameter(): true
    {
        if (!$this->typeNames) {
            $this->addCswException('typeNames', CswException::MISSINGPARAMETERVALUE);
        }

        return parent::validateParameter();
    }
}
