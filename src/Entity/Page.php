<?php

namespace App\Entity;

class Page
{
    protected ?string $slug = null;
    protected ?string $link = null;
    protected ?string $markdown = null;

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getMarkdown(): ?string
    {
        return $this->markdown;
    }

    public function setMarkdown(?string $markdown): static
    {
        $this->markdown = $markdown;
        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): static
    {
        $this->link = $link;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'markdown' => $this->markdown,
            'link' => $this->link,
        ];
    }

    public static function fromArray(array $data): static
    {
        $page = new static();
        $page->slug = $data['slug'] ?? null;
        $page->markdown = $data['markdown'] ?? null;
        $page->link = $data['link'] ?? null;
        return $page;
    }
}
