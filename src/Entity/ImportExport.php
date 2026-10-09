<?php

namespace App\Entity;

use App\Component\Utils\Password;
use App\Repository\ImportExportRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ImportExportRepository::class)]
#[ORM\Table(name: 'import_export')]
class ImportExport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Titel darf nicht leer sein.')]
    protected ?string $title = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Quelle darf nicht leer sein.')]
    protected ?string $source = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'User darf nicht leer sein.')]
    protected ?string $username = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Pfad darf nicht länger als {{ limit }} Zeichen sein.')]
    protected ?string $importFolder = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Length(max: 255, maxMessage: 'Pfad darf nicht länger als {{ limit }} Zeichen sein.')]
    #[Assert\Expression(
        expression: "this.getImportFolder() != '' || this.getExportFolder() != ''",
        message: 'Entweder Import oder Export muss ausgewählt sein.'
    )]
    #[Assert\Expression(
        expression: "not (this.getImportFolder() != '' && this.getExportFolder() != '')",
        message: 'Entweder Import oder Export muss ausgewählt sein.'
    )]
    protected ?string $exportFolder = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?\DateTimeInterface $lastRun = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $profileMapping = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $removeSourceData = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    protected ?bool $removeDataOnDestination = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    protected ?string $token = null;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $filter = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastRun(): ?\DateTimeInterface
    {
        return $this->lastRun;
    }

    public function setLastRun(?\DateTimeInterface $lastRun): static
    {
        $this->lastRun = $lastRun;
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

    public function getImportFolder(): ?string
    {
        return $this->importFolder;
    }

    public function setImportFolder(?string $importFolder): static
    {
        $this->importFolder = $importFolder;
        return $this;
    }

    public function getExportFolder(): ?string
    {
        return $this->exportFolder;
    }

    public function setExportFolder(?string $exportFolder): static
    {
        $this->exportFolder = $exportFolder;
        return $this;
    }

    public function setProfileMapping(?array $profileMapping): static
    {
        $this->profileMapping = $profileMapping;
        return $this;
    }

    public function getProfileMapping(): ?array
    {
        return $this->profileMapping;
    }

    public function getRemoveSourceData(): ?bool
    {
        return $this->removeSourceData;
    }

    public function setRemoveSourceData(?bool $removeSourceData): static
    {
        $this->removeSourceData = $removeSourceData;
        return $this;
    }

    public function getRemoveDataOnDestination(): ?bool
    {
        return $this->removeDataOnDestination;
    }

    public function setRemoveDataOnDestination(?bool $removeDataOnDestination): static
    {
        $this->removeDataOnDestination = $removeDataOnDestination;
        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token = null): static
    {
        $this->token = is_null($token) ? Password::generate(10) : $token;
        return $this;
    }

    public function getFilter(): array
    {
        return is_null($this->filter) ? ['eq' => ['public' => 1]] : $this->filter;
    }

    public function setFilter(array $filter): static
    {
        $this->filter = $filter;
        return $this;
    }
}

