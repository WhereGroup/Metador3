<?php

namespace App\Entity;

use App\Repository\WmsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WMS Entity for Web Map Service configuration
 */
#[ORM\Entity(repositoryClass: WmsRepository::class)]
#[ORM\Table(name: 'wms')]
class Wms
{
    public const TITLE_DEFAULT = 'Nicht Definiert';

    public static string $type = 'WMS';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private ?string $gcUrl = '';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $gmUrl = '';

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: 'Der Titel darf nicht leer sein')]
    private ?string $title = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $format = null;

    #[ORM\Column(type: 'json')]
    private array $formats = [];

    #[ORM\Column(type: 'json')]
    private array $layers = [];

    #[ORM\Column(type: 'json')]
    private array $layerList = [];

    #[ORM\Column(type: 'string', length: 20)]
    private ?string $version = null;

    #[ORM\Column(type: 'boolean')]
    private bool $visible = true;

    #[ORM\Column(type: 'float')]
    private float $opacity = 1.0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $priority = 0;

    public function __construct()
    {
        $this->formats = [];
        $this->layerList = [];
        $this->layers = [];
        $this->visible = true;
        $this->opacity = 1.0;
        $this->priority = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setGcUrl(?string $gcUrl): static
    {
        $this->gcUrl = $gcUrl;

        return $this;
    }

    public function getGcUrl(): ?string
    {
        return $this->gcUrl;
    }

    public function setGmUrl(?string $gmUrl): static
    {
        $this->gmUrl = $gmUrl;

        return $this;
    }

    public function getGmUrl(): ?string
    {
        return $this->gmUrl;
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

    public function setFormat(?string $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function setLayers(array $layers): static
    {
        $this->layers = $layers;

        return $this;
    }

    public function getLayers(): array
    {
        return $this->layers;
    }

    public function setLayerList(array $layerList): static
    {
        $this->layerList = $layerList;

        return $this;
    }

    public function addToLayerList(string $layer): static
    {
        $this->layerList[] = $layer;

        return $this;
    }

    public function getLayerList(): array
    {
        return $this->layerList;
    }

    public function setVersion(?string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setOpacity(float $opacity): static
    {
        $this->opacity = $opacity;

        return $this;
    }

    public function getOpacity(): float
    {
        return $this->opacity;
    }

    public function setPriority(?int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function setFormats(array $formats): static
    {
        $this->formats = $formats;

        return $this;
    }

    public function getFormats(): array
    {
        return $this->formats;
    }

    public function addFormat(string $format): static
    {
        $this->formats[] = $format;

        return $this;
    }
}
