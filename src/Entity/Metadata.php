<?php

namespace App\Entity;

use App\Repository\MetadataRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MetadataRepository::class)]
#[ORM\Table(name: 'metadata')]
#[ORM\Index(name: 'metadata_idx', columns: ['title', 'date', 'parent', 'source', 'public', 'profile', 'hierarchy_level'])]
class Metadata
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'integer')]
    private ?int $insertTime = null;

    #[ORM\Column(type: 'integer')]
    private ?int $updateTime = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $lockTime = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'insertuser_id', referencedColumnName: 'id')]
    private ?User $insertUser = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $insertUsername = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'updateuser_id', referencedColumnName: 'id')]
    private ?User $updateUser = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'lockuser_id', referencedColumnName: 'id')]
    private ?User $lockUser = null;

    #[ORM\Column(type: 'boolean')]
    private bool $locked = false;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $source = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    private ?string $hierarchyLevel = null;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $profile = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $parent = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $abstract = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $searchfield = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $anyText = null;

    #[ORM\Column(type: 'jsonb', nullable: true)]
    private ?array $object = null;

    #[ORM\Column(name: '`public`', type: 'boolean', nullable: true)]
    private ?bool $public = null;

    #[ORM\ManyToMany(targetEntity: Group::class, inversedBy: 'metadata')]
    #[ORM\JoinTable(name: 'metadata_groups')]
    private Collection $groups;

    #[ORM\ManyToMany(targetEntity: Address::class)]
    #[ORM\JoinTable(name: 'metadata_address')]
    private Collection $address;

    #[ORM\Column(name: '`date`', type: 'date', nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(name: '`date_stamp`', type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateStamp = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bboxn = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bboxe = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bboxs = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $bboxw = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $topicCategory = null;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->address = new ArrayCollection();
    }

    public function setInsertTime(?int $insertTime): static
    {
        $this->insertTime = $insertTime;
        return $this;
    }

    public function getInsertTime(): ?int
    {
        return $this->insertTime;
    }

    public function setUpdateTime(?int $updateTime): static
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    public function getUpdateTime(): ?int
    {
        return $this->updateTime;
    }

    public function setLockTime(?int $lockTime): static
    {
        $this->lockTime = $lockTime;
        return $this;
    }

    public function getLockTime(): ?int
    {
        return $this->lockTime;
    }

    public function setId(?string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): ?string
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

    public function setAbstract(?string $abstract): static
    {
        $this->abstract = $abstract;
        return $this;
    }

    public function getAbstract(): ?string
    {
        return $this->abstract;
    }

    public function setSearchfield(?string $searchfield): static
    {
        $this->searchfield = $searchfield;
        return $this;
    }

    public function getSearchfield(): ?string
    {
        return $this->searchfield;
    }

    public function getAnyText(): ?string
    {
        return $this->anyText;
    }

    public function setAnyText(?string $anyText): static
    {
        $this->anyText = $anyText;
        return $this;
    }

    public function setInsertUser(?User $insertUser): static
    {
        $this->insertUser = $insertUser;
        return $this;
    }

    public function getInsertUser(): ?User
    {
        return $this->insertUser;
    }

    public function setUpdateUser(?User $updateUser): static
    {
        $this->updateUser = $updateUser;
        return $this;
    }

    public function getUpdateUser(): ?User
    {
        return $this->updateUser;
    }

    public function setLockUser(?User $lockUser): static
    {
        $this->lockUser = $lockUser;
        return $this;
    }

    public function getLockUser(): ?User
    {
        return $this->lockUser;
    }

    public function getObject(): ?array
    {
        return $this->object ?? null;
    }

    public function setObject(?array $object): static
    {
        $this->object = $object ?? null;
        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): static
    {
        $this->public = $public;
        return $this;
    }

    public function getHierarchyLevel(): ?string
    {
        return $this->hierarchyLevel;
    }

    public function setHierarchyLevel(?string $hierarchyLevel): static
    {
        $this->hierarchyLevel = $hierarchyLevel;
        return $this;
    }

    public function getProfile(): ?string
    {
        return $this->profile;
    }

    public function setProfile(?string $profile): static
    {
        $this->profile = $profile;
        return $this;
    }

    public function addGroup(Group $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
        }
        return $this;
    }

    public function removeGroup(Group $group): static
    {
        $this->groups->removeElement($group);
        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function clearGroups(): static
    {
        $this->groups->clear();
        return $this;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->address->contains($address)) {
            $this->address[] = $address;
        }
        return $this;
    }

    public function removeAddress(Address $address): static
    {
        $this->address->removeElement($address);
        return $this;
    }

    public function clearAddress(): static
    {
        $this->address->clear();
        return $this;
    }

    /**
     * @return Collection<int, Address>
     */
    public function getAddress(): Collection
    {
        return $this->address;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getBboxn(): ?float
    {
        return $this->bboxn;
    }

    public function getBboxw(): ?float
    {
        return $this->bboxw;
    }

    public function setBboxw(?float $bboxw): static
    {
        $this->bboxw = $bboxw;
        return $this;
    }

    public function setBboxn(?float $bboxn): static
    {
        $this->bboxn = $bboxn;
        return $this;
    }

    public function getBboxs(): ?float
    {
        return $this->bboxs;
    }

    public function setBboxs(?float $bboxs): static
    {
        $this->bboxs = $bboxs;
        return $this;
    }

    public function getBboxe(): ?float
    {
        return $this->bboxe;
    }

    public function setBboxe(?float $bboxe): static
    {
        $this->bboxe = $bboxe;
        return $this;
    }

    public function getLocked(): bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;
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

    public function getParent(): ?string
    {
        return $this->parent;
    }

    public function setParent(?string $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getDateStamp(): ?\DateTimeInterface
    {
        return $this->dateStamp;
    }

    public function setDateStamp(?\DateTimeInterface $dateStamp): static
    {
        $this->dateStamp = $dateStamp;
        return $this;
    }

    public function getInsertUsername(): ?string
    {
        return $this->insertUsername;
    }

    public function setInsertUsername(?string $insertUsername): static
    {
        $this->insertUsername = $insertUsername;
        return $this;
    }

    public function getTopicCategory(): ?string
    {
        return $this->topicCategory;
    }

    public function setTopicCategory(?string $topicCategory): static
    {
        $this->topicCategory = $topicCategory;
        return $this;
    }
}
