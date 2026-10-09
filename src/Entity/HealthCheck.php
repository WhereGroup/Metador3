<?php

namespace App\Entity;

class HealthCheck
{
    private string $origin = 'system';
    private string $message = '';
    private array $parameters = [];
    private string $result = Log::INFO;

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setOrigin(string $origin): static
    {
        $this->origin = $origin;
        return $this;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function setParameters(array $parameters): static
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getResult(): string
    {
        return $this->result;
    }

    public function setResult(string $result): static
    {
        $this->result = $result;
        return $this;
    }
}
