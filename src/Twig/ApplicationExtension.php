<?php

namespace App\Twig;

use App\Event\ApplicationEvent;

use App\Service\Application;
use App\Service\Plugin;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Application $application,
        private readonly Plugin $plugin,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly RequestStack $requestStack
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('applicationGet', $this->applicationGet(...)),
            new TwigFunction('plugin_is_active', $this->pluginIsActive(...)),
            new TwigFunction('profile_is_active', $this->profileIsActive(...)),
        ];
    }

    public function applicationGet(string $type, ?string $key = null, mixed $default = null, bool $sort = false): mixed
    {
        $data = $this->application->getData();

        if (empty($data)) {
            $request = $this->requestStack->getCurrentRequest();
            $this->application->update($request->get('_route'), $request->get('_controller'));
            $this->eventDispatcher->dispatch(new ApplicationEvent($this->application, []), 'application.loading');
        }

        $data = $this->application->getData($type, $key, $default);

        if ($sort && is_array($data)) {
            usort($data, fn($a, $b) => $a['label'] <=> $b['label']);
        }

        return $data;
    }

    public function profileIsActive(string $profileKey): bool
    {
        $profiles = $this->plugin->getActiveProfiles();
        return isset($profiles[$profileKey]);
    }

    public function pluginIsActive(string $pluginKey): bool
    {
        $plugins = $this->plugin->getActivePlugins();
        return isset($plugins[$pluginKey]);
    }
}
