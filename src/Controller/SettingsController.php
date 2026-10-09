<?php

namespace App\Controller;

use App\Service\Configuration;
use App\Service\Plugin;
use App\Service\Source;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly Configuration $configService,
        private readonly Source $sourceService,
        private readonly Plugin $pluginService,
    )
    {}

    #[Route('/settings/show/{fragment}', name: 'metador_admin_settings', defaults: ['fragment' => ''], methods: ['GET', 'POST'])]
    public function index($fragment): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $pluginConfiguration = [];
        $sources = $this->sourceService->all();

        foreach ($this->getSettings() as $pluginKey => $pluginInfo) {
            // normalize
            foreach ($pluginInfo['settings'] as $settingKey => $setting) {
                $pluginInfo['settings'][$settingKey]['id'] = $pluginKey . '-config-' . $settingKey;
                $pluginInfo['settings'][$settingKey]['name'] = $pluginKey . '[' . $settingKey . ']';
                $pluginInfo['settings'][$settingKey]['placeholder']
                    = $setting['placeholder'] ?? '';
                $pluginInfo['settings'][$settingKey]['default']
                    = $setting['default'] ?? '';
                $pluginInfo['settings'][$settingKey]['description']
                    = $setting['description'] ?? '';
                $pluginInfo['settings'][$settingKey]['options']
                    = $setting['options'] ?? [];

                // Set active profiles as options
                if (isset($setting['optionSource']) && $setting['optionSource'] === 'source') {
                    /** @var Source $source */
                    foreach ($sources as $source) {
                        $pluginInfo['settings'][$settingKey]['options'][$source->getSlug()] = $source->getName();
                    }
                }

                $value = $this->configService->get($settingKey, 'plugin', $pluginKey, '');

                $pluginInfo['settings'][$settingKey]['value']
                    = !empty($value) ? $value : $pluginInfo['settings'][$settingKey]['default'];
            }

            $pluginConfiguration[$pluginKey] = $pluginInfo;
        }

        return $this->render('settings/index.html.twig', [
            'fragment' => $fragment,
            'pluginConfiguration' => $pluginConfiguration
        ]);
    }

    #[Route('/settings/update', name: 'metador_admin_settings_update', methods: ['POST'])]
    public function update(RequestStack $requestStack): RedirectResponse
    {
        /** @var Request $request */
        $request = $requestStack->getMainRequest();
        $params = $request->request->all();

        foreach ($this->getSettings() as $pluginKey => $pluginInfo) {
            foreach ($pluginInfo['settings'] as $settingKey => $setting) {
                if (!isset($params[$pluginKey][$settingKey])) {
                    $this->configService->remove($settingKey, 'plugin', $pluginKey);
                    continue;
                }

                $this->configService->set($settingKey, $params[$pluginKey][$settingKey], 'plugin', $pluginKey);
            }
        }

        return $this->redirectToRoute('metador_admin_settings');
    }
    // Todo: plugin.yml?
    private function getSettings(): array
    {
        $result = [];

        $settings = $this->pluginService->getPlugins();

        // Read system config
//        $coreSettings = Yaml::parse(file_get_contents(__DIR__ . '/../Resources/config/plugin.yml'));
//        $settings = array_merge_recursive($coreSettings, $settings);
//        unset($coreSettings);

        foreach ($settings as $pluginKey => $pluginInfo) {
            if ((isset($pluginInfo['active']) && $pluginInfo['active'] === false) || !isset($pluginInfo['settings'])) {
                continue;
            }

            $result[$pluginKey] = $pluginInfo;
        }

        return $result;
    }
}
