<?php

namespace App\Twig;

use App\Repository\ConfigurationRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConfigExtension extends AbstractExtension
{
    public function __construct(private readonly ConfigurationRepository $configuration)
        {}
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_frontend_settings', $this->getFrontendSettings(...)),
            new TwigFunction('get_setting', $this->getSetting(...)),
        ];
    }

    public function getSetting($key, $filterType = null, $filterValue = null, $default = null)
    {
        return $this->configuration->get($key, $filterType, $filterValue, $default);
    }

    public function getFrontendSettings(): array
    {
        $config = [];
// Todo: refactor
//        foreach ($this->plugin->getActivePlugins() as $pluginKey => $plugin) {
//            if (!isset($plugin['settings']) || empty($plugin['settings'])) {
//                continue;
//            }
//
//            foreach ($plugin['settings'] as $key => $setting) {
//                if (!isset($setting['frontend']) || $setting['frontend'] !== true) {
//                    continue;
//                }
//
//
//                $config[$key] = $this->configuration->get(
//                    $key,
//                    'plugin',
//                    $pluginKey,
//                    isset($setting['default']) ? $setting['default'] : null
//                );
//            }
//        }

        return $config;
    }
}
