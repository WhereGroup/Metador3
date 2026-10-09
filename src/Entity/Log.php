<?php

namespace App\Entity;

use App\Repository\LogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogRepository::class)]
#[ORM\Table(name: 'log')]
class Log
{
    public const INFO = 'info';
    public const SUCCESS = 'success';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const DEBUG = 'debug';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    protected string $type = self::INFO;

    #[ORM\Column(type: 'text', nullable: true)]
    protected string $message = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $username = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $category = 'system';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $subcategory = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $operation = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $source = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $identifier = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected string $path = '';

    #[ORM\Column(type: 'json', nullable: true)]
    protected array $params = [];

    #[ORM\Column(type: 'datetime')]
    protected ?\DateTime $dateTime = null;

    // Non-persistent (transient) properties
    protected array $messageParameter = [];
    protected mixed $user = null;
    protected bool $flashMessage = false;

    public function __construct()
    {
        $this->dateTime = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message, ?array $parameter = null): static
    {
        $this->message = $message;

        if ($parameter !== null) {
            $this->messageParameter = $parameter;
        }

        return $this;
    }

    public function getMessageParameter(): array
    {
        return $this->messageParameter;
    }

    public function setMessageParameter(array $messageParameter): static
    {
        $this->messageParameter = $messageParameter;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path, array $params = []): static
    {
        $this->path = $path;

        if ($params !== []) {
            $this->params = $params;
        }

        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getUser(): mixed
    {
        return $this->user;
    }

    public function setUser(mixed $user): static
    {
        $this->user = $user;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getSubcategory(): string
    {
        return $this->subcategory;
    }

    public function setSubcategory(string $subcategory): static
    {
        $this->subcategory = $subcategory;
        return $this;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): static
    {
        $this->operation = $operation;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): static
    {
        $this->identifier = $identifier;
        return $this;
    }

    public function getDateTime(): ?\DateTime
    {
        return $this->dateTime;
    }

    public function setDateTime(?\DateTime $dateTime): static
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    public function isFlashMessage(): bool
    {
        return $this->flashMessage;
    }

    public function setFlashMessage(bool $flashMessage = true): static
    {
        $this->flashMessage = $flashMessage;
        return $this;
    }
}

