<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;
use Exception;

abstract class Operation
{
    /**
     * The list "WhereGroup\CoreBundle\Component\Search::$MAP_QUERY2SOURCE['metadata']" contains a lower case of the
     * each "iso queryables" item.
     */
    const ISO_QUERYABLES = [
        'Identifier',
        'Title',
        'AnyText',
        'Subject'
    ];

    /**
     * The list "WhereGroup\CoreBundle\Component\Search::$MAP_QUERY2SOURCE['metadata']" contains a lower case of the
     * each "additional queryables" item.
     */
    const ADDITIONAL_QUERYABLES = [];

    /**
     * The element prefix for csw namespace
     */
    const PREFIX = 'csw';
    /**
     * The uri for csw namespace
     */
    const NSPACE = 'http://www.opengis.net/cat/csw/2.0.2';
    /**
     * The service name
     */
    const SERVICE = 'CSW';
    /**
     * The version
     */
    const VERSION = '2.0.2';

    public static $ALLOW_GET = true;

    public static $ALLOW_POST = true;

    /**
     * The list of key values pair to find parameters at request.
     */
    protected static array $parameterMap = [];

    protected array $exceptions = [];

    protected Csw $entity;
    /**
     * The service name (CSW)
     */
    protected string $service;

    /* request parameters */
    /**
     * The requested versions
     */
    protected string $version;
    /**
     * The requested output formats
     */
    protected string $outputFormat;

    protected array $supportedOutputFormats;

    public function __construct(Csw $entity)
    {
        $this->entity = $entity;
        $this->version = self::VERSION;
        $this->outputFormat = 'application/xml';
        $this->supportedOutputFormats = ["application/xml"];
    }

    protected function mapQueryable($name): mixed
    {
        if (isset(self::ISO_QUERYABLES[$name])) {
            return self::ISO_QUERYABLES[$name];
        } elseif (isset(self::ADDITIONAL_QUERYABLES[$name])) {
            return self::ADDITIONAL_QUERYABLES[$name];
        } else {
            return $name;
        }
    }

    protected static function parseCsl($string, $trim = true): array|false
    {
        return $trim ? preg_split('/\s?,\s?/', trim($string)) : preg_split('/,/', $string);
    }

    protected static function hasJointField(array $array1, array $array2): bool
    {
        foreach ($array1 as $item) {
            if (in_array($item, $array2)) {
                return true;
            }
        }

        return false;
    }

    protected static function normalizeString(?string $string = null): ?string
    {
        if ($string === null || $string === '' || trim($string) === '') {
            return null;
        } else {
            return trim($string);
        }
    }

    public function __destruct()
    {
        unset(
            $this->entity
        );
    }

    abstract public function getGETParameterMap();

    abstract public function getPOSTParameterMap();

    public function getEntity(): Csw
    {
        return $this->entity;
    }

    public function getService(): string
    {
        return $this->service;
    }

    public function setService($service): static
    {
        if ($service === self::SERVICE) {
            $this->service = $service;
        } elseif ($service === null || $service === '') {
            $this->addCswException('service', CswException::INVALIDPARAMETERVALUE);
        } else {
            $this->addCswException('service', CswException::MISSINGPARAMETERVALUE);
        }

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): void
    {
        if ($version === self::VERSION) {
            $this->version = $version;
        }
    }

    public function validateParameter(): true
    {
        if ($this->version !== self::VERSION) {
            $this->addCswException('version', CswException::INVALIDPARAMETERVALUE);
        }
        //  brake by first exception
        foreach ($this->exceptions as $exc) {
            throw $exc;
        }

        return true;
    }

    public function addCswException(string $locator, mixed $code): void
    {
        $found = false;
        /* @var CswException $exception */
        foreach ($this->exceptions as $exception) {
            if ($exception->getMessage() === $locator && $exception->getCode() === $code) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $this->exceptions[] = new CswException($locator, $code);
        }
    }

    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $this->setParameter($key, $value);
        }
    }

    protected function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'version':
                $this->setVersion($value);
                break;
            case 'service':
                $this->setService($value);
                break;
            case 'outputFormat':
                $this->setOutputFormat($value);
                break;
            case 'request':
                break;
            default:
                throw new Exception('!!!!!not defined parameter:' . $name);
        }
    }

    public function getOutputFormat(): string
    {
        return $this->outputFormat;
    }

    public function setOutputFormat($outputFormat): void
    {
        if ($outputFormat && is_string($outputFormat)) { # GET request
            if (!in_array($outputFormat, $this->supportedOutputFormats)) {
                $this->addCswException('outputFormat', CswException::INVALIDPARAMETERVALUE);
            } else {
                $this->outputFormat = $outputFormat;
            }
        } elseif ($outputFormat && !in_array($this->outputFormat, $outputFormat)) {
            $this->addCswException('outputFormat', CswException::INVALIDPARAMETERVALUE);
        }
    }
//
//    // Todo: used?
//    public function getContentSet(): ContentSet
//    {
//        return new ContentSet($this);
//    }

    protected function isListAtList($name, $list, array $values, $mandatory = false): bool
    {
        $result = null;
        foreach ($list as $item) {
            if ($result === null) {
                $result = $this->isStringAtList($name, $item, $values, $mandatory);
            } elseif (!$this->isStringAtList($name, $item, $values, $mandatory)) {
                $result = false;
            }
        }

        return $result !== null && $result !== false;
    }

    protected function isStringAtList(string $name, mixed $value, array $values, $mandatory = false): bool
    {
        $validString = $value !== null && is_string($value) && $value !== '';
        if ($validString && in_array($value, $values)) {
            return true;
        } elseif ($validString && !in_array($value, $values)) {
            $this->addCswException($name, CswException::INVALIDPARAMETERVALUE);

            return false;
        } elseif ($mandatory && (!$validString || !in_array($value, $values))) {
            $this->addCswException($name, CswException::INVALIDPARAMETERVALUE);

            return false;
        } else {
            return false;
        }
    }

    protected function getPositiveInteger($name, $intToTest): ?int
    {
        if (($int = self::getInteger($name, $intToTest)) !== null) {
            if ($int >= 0) {
                return $int;
            } else {
                $this->addCswException($name, CswException::INVALIDPARAMETERVALUE);

                return null;
            }
        } else {
            return null;
        }
    }

    protected function getInteger($name, $intToTest): ?int
    {
        if (is_int($intToTest)) {
            return $intToTest;
        } elseif (ctype_digit(trim($intToTest))) {
            return (int)trim($intToTest);
        } else {
            $this->addCswException($name, CswException::INVALIDPARAMETERVALUE);

            return null;
        }
    }

    protected function getGreaterThan($name, $numberToTest, $number): float|int|null
    {
        if ((is_int($numberToTest) || is_float($numberToTest)) && $numberToTest > $number) {
            return $numberToTest;
        } else {
            $this->addCswException($name, CswException::INVALIDPARAMETERVALUE);

            return null;
        }
    }
}
