<?php

namespace App\Component\CatalogueService;

use App\Component\CatalogueService\Search\GmlFilterReader;
use App\Service\Search\Expression;
use App\Service\Search\ExprHandler;
use DOMElement;
use Exception;


class TransactionOperation
{

    private string $type;

    private string $typeName;

    private string $handle;

    private array $items;

    private ExprHandler $exprHandler;

    private Expression $constraint;

    public function __construct(string $type, ExprHandler $exprHandler)
    {
        $this->type = $type;
        $this->items = [];
        $this->exprHandler = $exprHandler;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTypeName(): string
    {
        return $this->typeName;
    }

    public function setTypeName(string $typeName): self
    {
        $this->typeName = $typeName;

        return $this;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function setHandle(string $handle): self
    {
        $this->handle = $handle;

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this;
    }

    public function getConstraint(): Expression
    {
        return $this->constraint;
    }

    public function setConstraint(DOMElement $filter): self
    {
        $this->constraint = GmlFilterReader::readFromCsw($filter, $this->exprHandler);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'handle':
                $this->setHandle($value);
                break;
            case 'typeName':
                $this->setTypeName($value);
                break;
            default:
                throw new Exception('Transaction: not defined parameter:'.$name);
        }
    }
//
//    public function validateParameter()
//    {
//        ;
//    }
}
