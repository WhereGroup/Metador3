<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;

final class GetCapabilities extends Operation
{
    public static $SECTIONS = [
        'ServiceIdentification',
        'ServiceProvider',
        'OperationsMetadata',
        'Filter_Capabilities',
    ];

    /**
     * {@inheritdoc}
     */
    protected static array $parameterMap = [
        'version' => null,
        'service' => '/csw:GetCapabilities/@service',
        'acceptVersion' => '/csw:GetCapabilities/ows:AcceptVersions/ows:Version/text()',
        'section' => '/csw:GetCapabilities/ows:Sections/ows:Section/text()',
        'outputFormat' => '/csw:GetCapabilities/ows:AcceptFormats/ows:OutputFormat/text()',
    ];

    /**
     * @var string url for csw basic
     */
    protected $urlBasic;

    /**
     * @var string url for csw transaction
     */
    protected $urlTransaction;

    /* Request parameters */

    /**
     * The request parameter "acceptVersion"
     * @var array $acceptVersion
     */
    protected $acceptVersion;

    /**
     * The request parameter "section"
     * @var array $section
     */
    protected $section;

    /**
     * The list of supported ISO queryables
     * @var array $isoqueryables
     */
    protected $isoqueryables;

    /**
     * The list of supported additional queryables
     * @var array $addedqueryables
     */
    protected $addedqueryables;

    /**
     * {@inheritdoc}
     */
    public function __construct(Csw $entity, $urlBasic, $urlTAction)
    {
        parent::__construct($entity);
        $this->urlBasic = $urlBasic;
        $this->urlTransaction = $urlTAction;
        $this->section = [];
        $this->isoqueryables = self::ISO_QUERYABLES;
        $this->addedqueryables = self::ADDITIONAL_QUERYABLES;
    }

    public function getIsoqueryables(): array
    {
        return $this->isoqueryables;
    }

    public function getAddedqueryables(): array
    {
        return $this->addedqueryables;
    }

    public function getUrlBasic(): string
    {
        return $this->urlBasic;
    }

    public function setUrlBasic($urlBasic): static
    {
        $this->urlBasic = $urlBasic;

        return $this;
    }

    public function getUrlTransaction(): string
    {
        return $this->urlTransaction;
    }

    public function setUrlTransaction($urlTransaction): static
    {
        $this->urlTransaction = $urlTransaction;

        return $this;
    }

    public function getGETParameterMap(): array
    {
        return array_keys(self::$parameterMap);
    }

    public function getPOSTParameterMap(): array
    {
        $parameters = [];
        foreach (self::$parameterMap as $key => $value) {
            if ($value !== null) {
                $parameters[$value] = $key;
            }
        }

        return $parameters;
    }

    public function getAcceptVersion(): array
    {
        return $this->acceptVersion;
    }

    public function setAcceptVersion($value): void
    {
        if ($value && is_string($value)) { # GET request
            $this->acceptVersion = self::parseCsl($value);
        } elseif ($value && is_array($value)) { # POST request
            $this->acceptVersion = $value;
        }
    }

    public function getSection(): array
    {
        return $this->section;
    }

    public function setSection(mixed $section): void
    {
        $sectionArr = [];
        if ($section && is_string($section)) { # GET request
            $sectionArr = self::parseCsl($section);
        } elseif ($section && is_array($section)) { # POST request or parsed GET
            $sectionArr = $section;
        }
        if (count($sectionArr) > 0) {
            foreach ($sectionArr as $item) {
                if (!in_array($item, self::$SECTIONS)) {
                    $this->addCswException('section', CswException::INVALIDPARAMETERVALUE);
                }
            }
            $this->section = $sectionArr;
        }
    }

    public function setParameter($name, $value): void
    {
        switch ($name) {
            case 'acceptVersion':
                $this->setAcceptVersion($value);
                break;
            case 'section':
                $this->setSection($value);
                break;
            default:
                parent::setParameter($name, $value);
        }
    }

    public function validateParameter(): true
    {
        if ($this->acceptVersion && !in_array($this->version, $this->acceptVersion)) {
            $this->exceptions[] = new CswException('acceptVersion', CswException::VERSIONNEGOTIATIONFAILED);
        }
        parent::validateParameter();

        return true;
    }
}
