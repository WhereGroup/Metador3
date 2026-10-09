<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;
use App\Service\Search\ExprHandler;


abstract class FindRecord extends Operation
{
    const QUERYABLE_MAP = [
        'Identifier' => 'id',
        'Title' => 'title',
        'Abstract' => 'abstract',
    ];
    const RESULTTYPE_HITS = 'hits';
    const RESULTTYPE_RESULTS = 'results';
    const RESULTTYPE_VALIDATE = 'validate';

    const ELEMENTSET_BRIEF = 'brief';
    const ELEMENTSET_SUMMARY = 'summary';
    const ELEMENTSET_FULL = 'full';

    /* supported element sets */
    const ELEMENTSET = ['brief', 'summary', 'full'];
    const RESULTTYPE = ['hits', 'results', 'validate'];

    protected string $outputSchema;

    protected string $elementSetName;

    protected ExprHandler $exprHandler;

    /* @var $constraint */
    protected $constraint;

    public function __construct(
        Csw $entity = null,
        ExprHandler $exprHandler
    ) {
        parent::__construct($entity);
        $this->exprHandler = $exprHandler;
        $this->outputSchema = "http://www.isotc211.org/2005/gmd";
        $this->elementSetName = 'full';
    }

    public function getConstraint()
    {
        return $this->constraint;
    }

    abstract public function setConstraint($constraintContent);

    public function getOutputSchema(): string
    {
        return $this->outputSchema;
    }

    public function setOutputSchema($outputSchema): static
    {
        // use function to check only
        self::isStringAtList('outputSchema', $outputSchema, [$this->outputSchema], false);

        return $this;
    }

    public function getElementSetName(): string
    {
        return $this->elementSetName;
    }

    public function setElementSetName($elementSetName): static
    {
        if (self::isStringAtList('ElementSetName', $elementSetName, self::ELEMENTSET, false)) {
            $this->elementSetName = $elementSetName;
        }

        return $this;
    }

    public function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'elementSetName':
                $this->setElementSetName($value);
                break;
            case 'outputSchema':
                $this->setOutputSchema($value);
                break;
            default:
                parent::setParameter($name, $value);
        }
    }
}
