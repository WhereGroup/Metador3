<?php

namespace App\Controller\Logging;

use App\Service\Configuration;
use App\Service\Plugin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class PluginController extends AbstractController
{
    const PLUGIN_KEY = 'metador-logging';

    public function viewAction(RequestStack $requestStack, Configuration $configuration, Plugin $pluginService): Response
    {
        /** @var Request $request */
        $request = $requestStack->getMainRequest();

        $pluginInformation = $pluginService->getPlugin(self::PLUGIN_KEY);
        $pluginConfiguration = $pluginInformation['config'] ?? [];

        if ($request->getMethod() === 'POST') {
            $params = $request->request->get(self::PLUGIN_KEY);

            foreach ($params as $key => $value) {
                $pluginConfiguration[$key] = $value;
                $configuration->set($key, $value, 'Plugin', self::PLUGIN_KEY);
            }
        } else {
            foreach ($configuration->getAll('Plugin', self::PLUGIN_KEY) as $config) {
                $pluginConfiguration[$config['key']] = $config['value'];
            }
        }

        return $this->render('logging/Plugin/view.html.twig', [
            'pluginKey' => self::PLUGIN_KEY,
            'pluginConfiguration' => $pluginConfiguration
        ]);
    }
}
