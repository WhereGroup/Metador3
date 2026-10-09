<?php

namespace App\Entity;

use App\Repository\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'groups')]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'role', type: 'string', length: 255, unique: true)]
    protected ?string $role = null;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'groups')]
    protected Collection $users;

    #[ORM\ManyToMany(targetEntity: Metadata::class, mappedBy: 'groups')]
    protected Collection $metadata;

    #[ORM\Column(name: 'description', type: 'text', nullable: true)]
    protected ?string $description = null;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->metadata = new ArrayCollection();
    }

    public function getRole(): ?string
    {
        return $this->role ? mb_strtoupper($this->role, 'UTF-8') : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setRole(?string $role): static
    {
        if ($role !== null) {
            $role = preg_replace("/[^äöüßÄÖÜa-z0-9]/i", "_", mb_strtoupper($role, 'UTF-8'));
            $role = preg_replace("/[_]+/", "_", $role);
            $role = rtrim(ltrim($role, "_"), "_");

            if (!str_starts_with($role, "ROLE_")) {
                $role = "ROLE_" . $role;
            }
        }

        $this->role = $role;

        return $this;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $user->addGroup($this);
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function addMetadata(Metadata $metadata): static
    {
        if (!$this->metadata->contains($metadata)) {
            $this->metadata[] = $metadata;
        }

        return $this;
    }

    public function removeMetadata(Metadata $metadata): static
    {
        $this->metadata->removeElement($metadata);

        return $this;
    }

    /**
     * @return Collection<int, Metadata>
     */
    public function getMetadata(): Collection
    {
        return $this->metadata;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
