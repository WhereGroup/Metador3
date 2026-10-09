<?php

namespace App\Event;

use App\Component\Utils\ArrayParser;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ManualEvent extends Event
{
    protected $index = [];
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected $env;
    protected $manualType;

    public function add($path, $label, $class, $template, $role = null, $env = null): static
    {
        if ((!is_null($env) && $this->env !== $env) ||
            (!str_starts_with($path, $this->manualType)) ||
            (!is_null($role) && !$this->authorizationChecker->isGranted($role))) {
            return $this;
        }

        ArrayParser::set($this->index, $path . ':' . $label, [
            'manual'   => $path,
            'label'    => $label,
            'class'    => $class,
            'template' => $template,
            'role'     => $role,
            'env'      => $env
        ]);

        return $this;
    }

    public function getIndex($manual = null)
    {
        if (is_null($manual)) {
            return $this->index;
        }

        if (empty($this->index[$manual])) {
            return [];
        }

        ksort($this->index[$manual]);

        return $this->index[$manual];
    }

    public function setEnvironment($environment): static
    {
        $this->env = $environment;
        return $this;
    }

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker): static
    {
        $this->authorizationChecker = $authorizationChecker;
        return $this;
    }

    public function setManualType(string $manual): static
    {
        $this->manualType = $manual;
        return $this;
    }
}
