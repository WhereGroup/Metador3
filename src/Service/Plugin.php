<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Finder\Finder;

class Plugin
{
    protected string $pluginConfigDir;
    protected ?array $plugins;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        string $pluginConfigDir,
    ) {
        $this->pluginConfigDir = $pluginConfigDir;
        $this->plugins = $this->getPluginConfiguration();
    }

    public function getPlugins($groupBy = null): array
    {

        if ($groupBy === 'origin') {
            $result = [];

            foreach ($this->plugins as $key => $plugin) {
                $result[$plugin['origin']][$key] = $plugin;
            }

            return $result;
        }

        return $this->plugins;
    }

    /**
     * @throws Exception
     */
    public function getResource($profile, $resource): false|string
    {
        return file_get_contents($this->getResourcesPath($profile, $resource));
    }

    public function getResourcesPath($profile, $resource): false|string
    {
        $filepath = $this->parameterBag->get('kernel.project_dir') . '/templates/' .
            $this->getPluginTemplatePath($profile) . '/Resources/' . $resource;

        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new Exception("File not found or not readable!");
        }

        return $filepath;
    }

    public function getPath(string $profile, string $filePath): string
    {
        $path = $this->parameterBag->get('kernel.project_dir') . '/templates/' .
            $this->getPluginTemplatePath($profile) . $filePath;

        if (!file_exists($path) || !is_readable($path)) {
            throw new Exception("File not found or not readable!");
        }

        return $path;
    }

    public function getActivePlugins(): array
    {
        return array_filter($this->plugins, function ($key) {
            return $this->isActive($key);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getActiveProfiles(): array
    {
        return array_filter($this->plugins, function ($plugin) {
            return isset($plugin['type']) && strtolower($plugin['type']) === 'profile' &&
                isset($plugin['active']) && $plugin['active'];
        });
    }

    public function isActive($plugin): bool
    {
        return $this->plugins[$plugin]['active'] === true;
    }

    public function getPlugin($plugin): mixed
    {
        return $this->plugins[$plugin] ?? null;
    }

    public function getPluginClassName($plugin)
    {
        return $this->getPluginParameter($plugin, 'class_name');
    }

    public function getPluginTemplatePath($plugin)
    {
        return $this->getPluginParameter($plugin, 'template_path');
    }

    public function hasPluginParameter($plugin, $parameter): bool
    {
        return isset($this->plugins[$plugin][$parameter]);
    }

    public function getPluginParameter($plugin, $parameter)
    {
        if (!$this->hasPluginParameter($plugin, $parameter)) {
            throw new Exception("Unknown plugin or paramter");
        }

        return $this->plugins[$plugin][$parameter];
    }

    /**
     * @return array
     */
    protected function sortPlugins(): array
    {
        $plugins = [];
        $require = [
            'hasChildren' => [],
            'hasParent'   => [],
            'isSingle'    => []
        ];

        foreach ($this->plugins as $key => $value) {
            if (!empty($value['require'])) {
                foreach ($value['require'] as $plugin) {
                    $require['hasChildren'][$plugin][$key] = true;
                    $require['hasParent'][$key][$plugin] = true;
                }
            } else {
                $require['isSingle'][] = $key;
            }
        }

        // sort
        while (!empty($require['hasChildren'])) {
            foreach ($require['hasChildren'] as $key => $value) {
                if (!isset($require['hasParent'][$key])) {
                    // add plugin
                    $plugins[$key] = $this->plugins[$key];

                    unset($this->plugins[$key]);

                    // clean children array
                    unset($require['hasChildren'][$key]);

                    // clean parent array
                    foreach ($require['hasParent'] as $pKey => $pValue) {
                        if (isset($require['hasParent'][$pKey][$key])) {
                            unset($require['hasParent'][$pKey]);
                        }
                    }
                }
            }
        }

        unset($require);

        foreach ($this->plugins as $key => $value) {
            // add rest
            $plugins[$key] = $this->plugins[$key];
        }

        return $this->plugins = $plugins;
    }


    protected function getPluginConfiguration(): array
    {
        $plugins = [];

        if (is_dir($this->pluginConfigDir)) {
            $finder = new Finder();
            $finder
                ->files()
                ->in($this->pluginConfigDir)
                ->name('*.yml')
                ->name('*.yaml');

            foreach ($finder as $file) {
                $pluginConfig = $this->readYaml($file->getRealpath());

                foreach ($pluginConfig as $pluginName => $pluginData) {
                    $plugins[$pluginName] = $pluginData;

                    $envName = 'PLUGIN_' . strtoupper($pluginName);
                    $value = $_ENV[$envName] ?? $_SERVER[$envName] ?? getenv($envName);
                    $plugins[$pluginName]['active'] = is_string($value) && strtolower($value) === 'enabled';
                }
            }
        }

        ksort($plugins);

        return $plugins;
    }

    protected function readYaml($file): array
    {
        return Yaml::parse(file_get_contents($file));
    }
}
