<?php

namespace App\Service;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class Core
{
    protected Environment $templating;
    protected TranslatorInterface $translator;
    protected EventDispatcherInterface $eventDispatcher;
    protected AuthorizationCheckerInterface $authorizationChecker;
    protected KernelInterface $kernel;

    public function __construct(
        Environment $templating,
        EventDispatcherInterface $eventDispatcher,
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        KernelInterface $kernel
    ) {
        $this->templating = $templating;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
        $this->authorizationChecker = $authorizationChecker;
        $this->kernel = $kernel;
    }

    public function translate($id, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->trans($id, $parameters, $domain, $locale);
    }

    public function render($name, array $context = []): string
    {
        return $this->templating->render($name, $context);
    }

    public function isGranted($attributes, $object = null): bool
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }

    public function denyAccessUnlessGranted($attributes, $object = null, $message = 'Unable to access this page!'): void
    {
        if (!$this->authorizationChecker->isGranted($attributes, $object)) {
            throw new AccessDeniedException($message);
        }
    }

    public function dispatch($name, $event): object|string|null
    {
        return $this->eventDispatcher->dispatch($name, $event);
    }

    public function locateResource($resource): string
    {
        return $this->kernel->locateResource($resource);
    }
}
