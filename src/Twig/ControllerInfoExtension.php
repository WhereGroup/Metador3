<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ControllerInfoExtension extends AbstractExtension
{
    private ?string $name = null;
    private ?string $action = null;

    public function __construct(
        private readonly RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('controller_name', $this->getControllerName(...)),
            new TwigFunction('controller_action', $this->getControllerAction(...))
        ];
    }

    public function getControllerName(): string
    {
        $this->updateInformation();
        return $this->name ?? '';
    }

    public function getControllerAction(): string
    {
        $this->updateInformation();
        return $this->action ?? '';
    }

    private function updateInformation(): void
    {
        if ($this->name === null) {
            $request = $this->requestStack->getCurrentRequest();
            if ($request === null) {
                return;
            }

            $controller = $request->get('_controller');
            $matches = [];

            // Symfony 7.4 verwendet FQCN::method statt Bundle:Controller:action
            if (preg_match('/Controller\\\\(\w+)Controller/', $controller, $matches)) {
                $this->name = strtolower($matches[1]);
            } else {
                $this->name = '';
            }

            // Moderne Syntax: App\Controller\FooController::barAction oder ::bar
            if (preg_match('/::(\w+)(?:Action)?$/', $controller, $matches)) {
                $this->action = strtolower($matches[1]);
            } else {
                $this->action = '';
            }
        }
    }
}
