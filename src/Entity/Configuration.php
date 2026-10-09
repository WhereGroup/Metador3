<?php

namespace App\Entity;

use App\Repository\ConfigurationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConfigurationRepository::class)]
#[ORM\Table(name: 'configuration')]
class Configuration
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $filterType = null;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $filterValue = null;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $key = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $value = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $json = false;

    public function getFilterType(): ?string
    {
        return $this->filterType;
    }

    public function setFilterType(?string $filterType): static
    {
        $this->filterType = $filterType;
        return $this;
    }

    public function getFilterValue(): ?string
    {
        return $this->filterValue;
    }

    public function setFilterValue(?string $filterValue): static
    {
        $this->filterValue = $filterValue;
        return $this;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(?string $key): static
    {
        $this->key = $key;
        return $this;
    }

    public function getValue(): mixed
    {
        return $this->getJson() ? json_decode($this->value, true) : $this->value;
    }

    public function setValue(mixed $value): static
    {
        $this->setJson(false);

        if (!is_string($value) && !is_numeric($value)) {
            $value = json_encode($value);
            $this->setJson(true);
        }

        $this->value = $value;

        return $this;
    }

    public function getJson(): bool
    {
        return $this->json;
    }

    public function setJson(bool $json): static
    {
        $this->json = $json;
        return $this;
    }
}
