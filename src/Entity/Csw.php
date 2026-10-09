<?php

namespace App\Entity;

use App\Repository\CswRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CswRepository::class)]
#[ORM\Table(name: 'csw')]
class Csw
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Slug darf nicht leer sein.')]
    protected ?string $slug = null;

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Quelle darf nicht leer sein.')]
    protected ?string $source = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'User darf nicht leer sein.')]
    protected ?string $username = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $active = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $abstract = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $keywords = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $fees = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $accessConstraints = [];

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $providerName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $providerSite = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $individualName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $positionName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $phoneVoice = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $phoneFacsimile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $deliveryPoint = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $city = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $administrativeArea = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $postalCode = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $country = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $electronicMailAddress = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $onlineResourse = null;

    #[ORM\Column(name: '`insert`', type: 'boolean', nullable: true)]
    protected bool $insert = false;

    #[ORM\Column(name: '`update`', type: 'boolean', nullable: true)]
    protected bool $update = false;

    #[ORM\Column(name: '`delete`', type: 'boolean', nullable: true)]
    protected bool $delete = false;

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $profileMapping = [];

    #[ORM\Column(name: '`series_as_dataset`', type: 'boolean', nullable: true)]
    protected bool $seriesAsDataset = false;

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $filter = [];

    public function __construct()
    {
        $this->keywords = [];
        $this->accessConstraints = [];
        $this->profileMapping = [];
        $this->filter = [];
    }

    /**
     * Creates a Csw instance from properties.
     */
    public function fromArray(array $kv): static
    {
        $this->slug                  = $kv['slug'] ?? null;
        $this->source                = $kv['source'] ?? null;
        $this->title                 = $kv['title'] ?? null;
        $this->abstract              = $kv['abstract'] ?? null;
        $this->keywords              = $kv['keywords'] ?? [];
        $this->fees                  = $kv['fees'] ?? 'none';
        $this->accessConstraints     = $kv['accessConstraints'] ?? ['none'];
        $this->providerName          = $kv['providerName'] ?? null;
        $this->providerSite          = $kv['providerSite'] ?? null;
        $this->individualName        = $kv['individualName'] ?? null;
        $this->positionName          = $kv['positionName'] ?? null;
        $this->phoneVoice            = $kv['phoneVoice'] ?? null;
        $this->phoneFacsimile        = $kv['phoneFacsimile'] ?? null;
        $this->deliveryPoint         = $kv['deliveryPoint'] ?? null;
        $this->city                  = $kv['city'] ?? null;
        $this->administrativeArea    = $kv['administrativeArea'] ?? null;
        $this->postalCode            = $kv['postalCode'] ?? null;
        $this->country               = $kv['country'] ?? null;
        $this->electronicMailAddress = $kv['electronicMailAddress'] ?? null;
        $this->onlineResourse        = $kv['onlineResourse'] ?? null;
        $this->insert                = (bool) ($kv['insert'] ?? false);
        $this->update                = (bool) ($kv['update'] ?? false);
        $this->delete                = (bool) ($kv['delete'] ?? false);
        $this->seriesAsDataset       = (bool) ($kv['seriesAsDataset'] ?? false);
        $this->profileMapping        = $kv['profileMapping'] ?? [];
        $this->filter                = $kv['filter'] ? (is_array($kv['filter']) ? $kv['filter'] : json_decode($kv['filter'], true)) : [];

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
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

    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(mixed $active): static
    {
        $this->active = (bool) $active;
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

    public function getAbstract(): ?string
    {
        return $this->abstract;
    }

    public function setAbstract(?string $abstract): static
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getFees(): ?string
    {
        return $this->fees;
    }

    public function setFees(?string $fees): static
    {
        $this->fees = $fees;
        return $this;
    }

    public function getAccessConstraints(): array
    {
        return $this->accessConstraints;
    }

    public function setAccessConstraints(array $accessConstraints): static
    {
        $this->accessConstraints = $accessConstraints;
        return $this;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }

    public function setProviderName(?string $providerName): static
    {
        $this->providerName = $providerName;
        return $this;
    }

    public function getProviderSite(): ?string
    {
        return $this->providerSite;
    }

    public function setProviderSite(?string $providerSite): static
    {
        $this->providerSite = $providerSite;
        return $this;
    }

    public function getIndividualName(): ?string
    {
        return $this->individualName;
    }

    public function setIndividualName(?string $individualName): static
    {
        $this->individualName = $individualName;
        return $this;
    }

    public function getPositionName(): ?string
    {
        return $this->positionName;
    }

    public function setPositionName(?string $positionName): static
    {
        $this->positionName = $positionName;
        return $this;
    }

    public function getPhoneVoice(): ?string
    {
        return $this->phoneVoice;
    }

    public function setPhoneVoice(?string $phoneVoice): static
    {
        $this->phoneVoice = $phoneVoice;
        return $this;
    }

    public function getPhoneFacsimile(): ?string
    {
        return $this->phoneFacsimile;
    }

    public function setPhoneFacsimile(?string $phoneFacsimile): static
    {
        $this->phoneFacsimile = $phoneFacsimile;
        return $this;
    }

    public function getDeliveryPoint(): ?string
    {
        return $this->deliveryPoint;
    }

    public function setDeliveryPoint(?string $deliveryPoint): static
    {
        $this->deliveryPoint = $deliveryPoint;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    public function setAdministrativeArea(?string $administrativeArea): static
    {
        $this->administrativeArea = $administrativeArea;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getElectronicMailAddress(): ?string
    {
        return $this->electronicMailAddress;
    }

    public function setElectronicMailAddress(?string $electronicMailAddress): static
    {
        $this->electronicMailAddress = $electronicMailAddress;
        return $this;
    }

    public function getOnlineResourse(): ?string
    {
        return $this->onlineResourse;
    }

    public function setOnlineResourse(?string $onlineResourse): static
    {
        $this->onlineResourse = $onlineResourse;
        return $this;
    }

    public function getInsert(): bool
    {
        return $this->insert;
    }

    public function setInsert(bool $insert): static
    {
        $this->insert = $insert;
        return $this;
    }

    public function getUpdate(): bool
    {
        return $this->update;
    }

    public function setUpdate(bool $update): static
    {
        $this->update = $update;
        return $this;
    }

    public function getDelete(): bool
    {
        return $this->delete;
    }

    public function setDelete(bool $delete): static
    {
        $this->delete = $delete;
        return $this;
    }

    public function getSeriesAsDataset(): bool
    {
        return $this->seriesAsDataset;
    }

    public function setSeriesAsDataset(bool $seriesAsDataset): static
    {
        $this->seriesAsDataset = $seriesAsDataset;
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

