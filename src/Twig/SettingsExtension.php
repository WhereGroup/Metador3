<?php

namespace App\Twig;

use App\Service\Configuration;
use App\Service\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly Configuration $configuration
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_frontend_settings', $this->getFrontendSettings(...)),
            new TwigFunction('get_setting', $this->getSetting(...)),
        ];
    }

    public function getSetting(string $key, ?string $filterType = null, ?string $filterValue = null, mixed $default = null): mixed
    {
        return $this->configuration->get($key, $filterType, $filterValue, $default);
    }

    public function getFrontendSettings(): array
    {
        $config = [];

        foreach ($this->plugin->getActivePlugins() as $pluginKey => $plugin) {
            if (!isset($plugin['settings']) || empty($plugin['settings'])) {
                continue;
            }

            foreach ($plugin['settings'] as $key => $setting) {
                if (!isset($setting['frontend']) || $setting['frontend'] !== true) {
                    continue;
                }

                $config[$key] = $this->configuration->get(
                    $key,
                    'plugin',
                    $pluginKey,
                    $setting['default'] ?? null
                );
            }
        }

        return $config;
    }
}
