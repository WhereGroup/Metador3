<?php

namespace App\Service;

use App\Repository\SourceRepository;
use Doctrine\ORM\NoResultException;
use App\Entity\Source as SourceEntity;

class Source implements SourceInterface
{
    protected ?SourceRepository $repo = null;

    public function __construct(SourceRepository $repo)
    {
        $this->repo = $repo;
    }

    public function __destruct()
    {
        unset(
            $this->repo
        );
    }


    public function count(): int
    {
        try {
            return $this->repo->countAll();
        } catch (NoResultException $e) {
            return 0;
        }
    }

    public function get($id)
    {
        return $this->repo->findOneById($id);
    }

    public function all()
    {
        return $this->repo->findAll();
    }

    public function allValues(): array
    {
        $array = [];

        foreach ($this->all() as $source) {
            $array[$source->getName()] = $source->getSlug();
        }

        return $array;
    }

    public function findBySlug($slug)
    {
        return $this->repo->findOneBySlug($slug);
    }

    public function save($entity): static
    {
        $this->repo->save($entity);

        return $this;
    }

    public function set($slug, $name, $description = '', $system = false): void
    {
        $entity = new SourceEntity();
        $entity
            ->setSlug($slug)
            ->setName($name)
            ->setDescription($description)
            ->setSystem($system);
        $this->save($entity);
    }

    public function remove($entity): static
    {
        $this->repo->remove($entity);

        return $this;
    }
}
