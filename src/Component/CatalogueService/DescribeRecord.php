<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;

class DescribeRecord extends Operation
{

    protected static array $parameterMap = [
        'version' => '/csw:DescribeRecord/@version',
        'service' => '/csw:DescribeRecord/@service',
        'typeName' => '/csw:DescribeRecord/csw:TypeName/text()',
        'outputFormat' => '/csw:DescribeRecord/@outputFormat',
    ];

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

    /* Request parameters */

    protected string $namespace; // not yet implemented

    protected array|string $typeName;

    public function __construct(Csw $entity)
    {
        parent::__construct($entity);
        $this->typeName = ['gmd:MD_Metadata'];
    }

    public function getTypeName(): array|string
    {
        return $this->typeName;
    }

    public function setTypeName(string|array $typeName): static
    {
        $_typeName = [];
        if ($typeName && is_string($typeName)) { # GET request or POST single typeName
            $_typeName = self::parseCsl($typeName);
        } elseif ($typeName && is_array($typeName)) { # POST request
            $_typeName = $typeName;
        }
        if ($this->isListAtList('typeName', $_typeName, $this->typeName, false)) {
            $this->typeName = $_typeName;
        }

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'typeName':
                $this->setTypeName($value);
                break;
            default:
                parent::setParameter($name, $value);
        }
    }
}
