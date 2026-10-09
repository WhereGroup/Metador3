<?php

namespace App\Entity;

use App\Repository\CswHarvesterRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CswHarvesterRepository::class)]
#[ORM\Table(name: 'csw_harvester')]
class CswHarvester
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'Titel darf nicht leer sein.')]
    protected ?string $title = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'URL darf nicht leer sein.')]
    protected ?string $url = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Quelle darf nicht leer sein.')]
    protected ?string $source = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'User darf nicht leer sein.')]
    protected ?string $username = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $profileMapping = [];

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $filter = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getProfileMapping(): array
    {
        return $this->profileMapping;
    }

    public function setProfileMapping(array $profileMapping): static
    {
        $this->profileMapping = $profileMapping;
        return $this;
    }

    public function getFilter(): array
    {
        return $this->filter;
    }

    public function setFilter(array $filter): static
    {
        $this->filter = $filter;
        return $this;
    }
}

