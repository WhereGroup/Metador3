<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'address')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $uuid = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $individualName = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $organisationName = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $department = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $positionName = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $email = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $country = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $administrativeArea = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $deliveryPoint = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $city = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $postalCode = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $voice = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $facsimile = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $url = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $urlDescription = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $hoursOfService = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $searchfield = '';

    /**
     * @var Collection<int, Metadata>
     */
    #[ORM\ManyToMany(targetEntity: Metadata::class, mappedBy: 'address')]
    private Collection $metadata;

    public function __construct()
    {
        $this->metadata = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = (string) $uuid;

        return $this;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setIndividualName(?string $individualName): self
    {
        $this->individualName = $individualName;

        return $this;
    }

    public function getIndividualName(): ?string
    {
        return $this->individualName;
    }

    public function setOrganisationName(string $organisationName): self
    {
        $this->organisationName = $organisationName;

        return $this;
    }

    public function getOrganisationName(): string
    {
        return $this->organisationName;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;

        return $this;
    }

    public function setPositionName(?string $positionName): self
    {
        $this->positionName = $positionName;

        return $this;
    }

    public function getPositionName(): ?string
    {
        return $this->positionName;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setAdministrativeArea(?string $administrativeArea): self
    {
        $this->administrativeArea = $administrativeArea;

        return $this;
    }

    public function getAdministrativeArea(): ?string
    {
        return $this->administrativeArea;
    }

    public function setDeliveryPoint(?string $deliveryPoint): self
    {
        $this->deliveryPoint = $deliveryPoint;

        return $this;
    }

    public function getDeliveryPoint(): ?string
    {
        return $this->deliveryPoint;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setVoice(?string $voice): self
    {
        $this->voice = $voice;

        return $this;
    }

    public function getVoice(): ?string
    {
        return $this->voice;
    }

    public function setFacsimile(?string $facsimile): self
    {
        $this->facsimile = $facsimile;

        return $this;
    }

    public function getFacsimile(): ?string
    {
        return $this->facsimile;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrlDescription(?string $urlDescription): self
    {
        $this->urlDescription = $urlDescription;

        return $this;
    }

    public function getUrlDescription(): ?string
    {
        return $this->urlDescription;
    }

    public function setHoursOfService(?string $hoursOfService): self
    {
        $this->hoursOfService = $hoursOfService;

        return $this;
    }

    public function getHoursOfService(): ?string
    {
        return $this->hoursOfService;
    }

    public function setSearchfield(?string $searchfield): self
    {
        $this->searchfield = $searchfield;

        return $this;
    }

    public function getSearchfield(): ?string
    {
        return $this->searchfield;
    }

    public function addMetadata(Metadata $metadata): self
    {
        if (!$this->metadata->contains($metadata)) {
            $this->metadata[] = $metadata;
            $metadata->addAddress($this);
        }

        return $this;
    }

    public function removeMetadata(Metadata $metadata): self
    {
        if ($this->metadata->removeElement($metadata)) {
            $metadata->removeAddress($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }
}
