<?php

namespace App\Entity;

use App\Repository\KeywordRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: KeywordRepository::class)]
#[ORM\Table(name: 'keyword')]
class Keyword implements \ArrayAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Identifier darf nicht leer sein.')]
    private ?string $identifier = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $repository = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Titel darf nicht leer sein.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'Datum darf nicht leer sein.')]
    private ?DateTime $date = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'Datumstyp darf nicht leer sein.')]
    private ?string $dateType = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'conditional'])]
    #[Assert\NotBlank(message: 'Kardinalität darf nicht leer sein.')]
    private ?string $cardinality = null;

    #[ORM\Column(type: Types::JSON)]
    private ?array $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $profiles = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDate(?DateTime $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDateType(?string $dateType): static
    {
        $this->dateType = $dateType;
        return $this;
    }

    public function getDateType(): ?string
    {
        return $this->dateType;
    }

    public function getCardinality(): ?string
    {
        return $this->cardinality;
    }

    public function setCardinality(?string $cardinality): static
    {
        $this->cardinality = $cardinality;
        return $this;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setIdentifier(?string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setRepository(?bool $repository): static
    {
        $this->repository = $repository;
        return $this;
    }

    public function getRepository(): ?bool
    {
        return $this->repository;
    }

    public function setProfiles(?string $profiles): static
    {
        $this->profiles = $profiles;
        return $this;
    }

    public function getProfiles(): ?string
    {
        return $this->profiles;
    }

    // ArrayAccess Implementation
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $getter = 'get' . ucfirst($offset);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $setter = 'set' . ucfirst($offset);
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $setter = 'set' . ucfirst($offset);
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        }
    }
}
