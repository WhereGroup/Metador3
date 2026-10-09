<?php

namespace App\Event;

use App\Entity\HealthCheck;
use App\Entity\Log;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Contracts\Translation\TranslatorInterface;

class HealthCheckEvent extends Event
{
    private $data = [];
    private $translator = null;
    private $hasError = false;
    private $errorCount = 0;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function add(HealthCheck $check) : HealthCheckEvent
    {
        // Translate
        $check->setMessage($this->translator->trans($check->getMessage(), $check->getParameters()));

        if ($check->getResult() !== Log::SUCCESS && $check->getResult() !== Log::INFO) {
            $this->hasError = true;
            ++$this->errorCount;
        }

        $this->data[] = $check;
        return $this;
    }

    public function hasError(): bool
    {
        return $this->hasError;
    }

    public function getErrorCount() : int
    {
        return $this->errorCount;
    }

    public function getLogs() : array
    {
        return $this->data;
    }
}
