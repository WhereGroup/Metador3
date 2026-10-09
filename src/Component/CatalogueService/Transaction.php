<?php

namespace App\Component\CatalogueService;

use App\Entity\Csw;

class Transaction extends Operation
{
    const INSERT = 'Insert';
    const UPDATE = 'Update';
    const DELETE = 'Delete';
    const ITEMS  = 'items';
    const ACTION = 'action';
    const FILTER = 'filter';
    const PARAMS = 'params';

    /**
     * {@inheritdoc}
     */
    protected static array $parameterMap = [
        '/csw:Transaction/@version' => 'version',
        '/csw:Transaction/@service' => 'service',
        '/csw:Transaction/@verboseResponse' => 'verboseResponse',
        '/csw:Transaction/@requestId' => 'requestId',
        '/csw:Transaction/csw:*' => [
            'Insert' => [
                self::ACTION => self::INSERT,
                self::ITEMS => './gmd:MD_Metadata',
                self::PARAMS => [
                    './@handle' => 'handle',
                    './@typeName' => 'typeName',
                ],
            ],
            'Update' => [
                self::ACTION => self::UPDATE,
                self::ITEMS => './gmd:MD_Metadata',
                self::FILTER => './csw:Constraint/ogc:Filter',
                self::PARAMS => [
                    './@handle' => 'handle',
                ],
            ],
            'Delete' => [
                self::ACTION => self::DELETE,
                self::ITEMS => './gmd:MD_Metadata',
                self::FILTER => './csw:Constraint/ogc:Filter',
                self::PARAMS => [
                    './@handle' => 'handle',
                    './@typeName' => 'typeName',
                ],
            ],
        ],
    ];

    private ?string $requestId;

    private bool $verboseResponse;

    private int $inserted;

    private int $updated;

    private int $deleted;

    public function __construct(?Csw $entity = null)
    {
        parent::__construct($entity);
        $this->verboseResponse = false;
        $this->requestId = null;
        $this->inserted = 0;
        $this->updated = 0;
        $this->deleted = 0;
    }

    public function getGETParameterMap(): array
    {
        return [];
    }

    public function getPOSTParameterMap(): array
    {
        return self::$parameterMap;
    }

    public function isTypeSupported($type): bool
    {
        switch ($type) {
            case self::INSERT:
                return $this->isInsertSupported();
            case self::UPDATE:
                return $this->isUpdateSupported();
            case self::DELETE:
                return $this->isDeleteSupported();
            default:
                throw new CswException($type, CswException::OPERATIONNOTSUPPORTED);
        }
    }

    public function isDeleteSupported(): bool
    {
        return $this->entity->getDelete();
    }

    public function isInsertSupported(): bool
    {
        return $this->entity->getInsert();
    }

    public function isUpdateSupported(): bool
    {
        return $this->entity->getUpdate();
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setRequestId(?string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    public function isVerboseResponse(): bool
    {
        return $this->verboseResponse;
    }

    public function setVerboseResponse(bool $verboseResponse): self
    {
        $this->verboseResponse = $verboseResponse;

        return $this;
    }

    public function getInserted(): int
    {
        return $this->inserted;
    }

    public function addInserted(int $inserted = 1): self
    {
        $this->inserted += $inserted;

        return $this;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function addUpdated(int $updated = 1): self
    {
        $this->updated += $updated;

        return $this;
    }

    public function getDeleted(): int
    {
        return $this->deleted;
    }

    public function addDeleted(int $deleted = 1): self
    {
        $this->deleted += $deleted;

        return $this;
    }

    public function setParameter(string $name, mixed $value): void
    {
        switch ($name) {
            case 'requestId':
                $this->setRequestId($value);
                break;
            case 'verboseResponse':
                $this->setVerboseResponse(boolval($value));
                break;
            default:
                parent::setParameter($name, $value);
        }
    }
}
