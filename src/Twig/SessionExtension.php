<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SessionExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('session_maxlifetime', $this->maxlifetime(...))
        ];
    }

    public function maxlifetime(): int
    {
        $request = $this->requestStack->getCurrentRequest();

        // Check if session was already started (without starting a new one)
        if ($request && $request->hasSession() && $request->getSession()->isStarted()) {
            return (int) ini_get("session.gc_maxlifetime");
        }

        return 0;
    }
}
