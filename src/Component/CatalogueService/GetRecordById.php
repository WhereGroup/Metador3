<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;
use App\Service\Search\Expression;
use App\Service\Search\ExprHandler;

class GetRecordById extends FindRecord
{

    public static array $parameterMap = [
        'version' => '/csw:GetRecordById/@version',
        'service' => '/csw:GetRecordById/@service',
        'outputSchema' => '/csw:GetRecordById/@outputSchema',
        'outputFormat' => '/csw:GetRecordById/@outputFormat',
        'elementSetName' => '/csw:GetRecordById/csw:ElementSetName/text()',
        'id' => '/csw:GetRecordById/csw:Id/text()',
    ];

//    protected $outputSchema;
    protected mixed $id;

    public function __construct(Csw $entity, ExprHandler $exprHandler)
    {
        parent::__construct($entity, $exprHandler);
        $this->supportedOutputFormats = ["application/xml", "text/html", "application/pdf"];
    }

    public function setConstraint($constraintContent): void
    {
        $identifier = "id";
        $parameters = [];

        $this->constraint = new Expression(
            $this->exprHandler->in($identifier, $constraintContent, $parameters),
            $parameters
        );
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

    public function getId(): mixed
    {
        return $this->id;
    }

    public function setId(mixed $id): self
    {
        if ($id && is_string($id)) {
            $this->id = $this->parseCsl($id);
        } elseif ($id && is_array($id)) {
            $this->id = $id;
        }
        $this->setConstraint($this->id);

        return $this;
    }

    public function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'id':
                $this->setId($value);
                break;
            default:
                parent::setParameter($name, $value);
        }
    }
}
