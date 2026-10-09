<?php

namespace App\Controller;

use App\Component\CsvResponse;
use App\Component\Utils\ArrayParser;
use App\Service\Configuration;
use App\Service\ConfigurationInterface;
use App\Service\CsvExport;
use App\Service\FrontendCommand;
use App\Service\Plugin;
use App\Service\Process;
use App\Service\Search\PrepareSearch;
use App\Service\Search\SolrSearch;
use App\Service\Source;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Component\AjaxResponse;
use App\Component\Exceptions\MetadataException;
use Twig\Environment;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly Source $source,
        private readonly Configuration $config,
        private readonly Plugin $plugin,
        private readonly Environment $templating,
        private readonly FrontendCommand $frontendCommand,
        private readonly Process $process,
    ) {
    }

    #[Route('/', name: 'metador_home', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'isHome'         => true,
            'showPopup'      => (bool) $_ENV['APP_SHOW_POPUP'],
            'sourceConfig'   => $this->getSourceConfiguration(),
            'hierarchyLevel' => $this->config->get('hierarchy_levels', 'plugin', 'metador_core')
        ]);
    }

    #[Route('/public/search', name: 'metador_search')]
    public function search(
        Request $request,
        ConfigurationInterface $configurationService,
        SolrSearch $search,
        User $userService,
        CsvExport $csvExport,
        FrontendCommand $frontendCommand,
    ): Response
    {
        $params   = $request->request->all();
        $download = $request->request->get('download', null);
        $response = [];
        if (isset($params["spatial"])) {
            $params["spatial"] = json_decode($params["spatial"], true);
        }

        $hits = $configurationService->get('search_hits', 'plugin', 'metador_core', 10);
        $user = $userService->getUserFromSession();
        PrepareSearch::setFilter($search, $params, $user, $download);
        PrepareSearch::setPagination($search, $params, $hits, $download);

        try {
            $searchResponse = $search->find();
        } catch (MetadataException $e) {
            $frontendCommand->displayError($response, $e->getMessage());
            return new AjaxResponse($response);
        }

        if (!is_null($download)) {
            return new CsvResponse(
                $csvExport->buildCsvArray(
                    $searchResponse['rows'] ?? []
                )
            );
        }

        $this
            ->renderTemplate($response, $searchResponse, $params)
            ->addBboxFrontendCommand($response, $searchResponse['rows']);

        return new AjaxResponse($response);
    }

    protected function renderTemplate(&$response, $searchResponse, $params): static
    {
        $response = array_merge($response, [
            'html' => $this->templating->render('home/result.html.twig', [
                'rows'   => $searchResponse['rows'],
                'paging' => $searchResponse['paging'],
            ]),
            'debug' => $params
        ]);

        return $this;
    }

    protected function addBboxFrontendCommand(&$response, $rows): static
    {
        $bboxParams = [];

        if (!empty($rows)) {
            foreach ($rows as $result) {
                if (!isset($result['object'])) {
                    continue;
                }

                $p =  $result['object'];

                if (!$p) {
                    continue;
                }

                $bbox = ArrayParser::get($p, 'bbox', null, true);

                if (!empty($p['_uuid']) && !empty($p['title']) && !empty($bbox['wLongitude']) && !empty($bbox['sLatitude']) && !empty($bbox['eLongitude']) && !empty($bbox['nLatitude'])) {
                    $bboxParams[] = [
                        'uuid' => $p['_uuid'],
                        'title' => $p['title'],
                        'west' => $bbox['wLongitude'],
                        'south' => $bbox['sLatitude'],
                        'east' => $bbox['eLongitude'],
                        'north' => $bbox['nLatitude'],
                    ];
                }
            }
        }

        $activePlugins = $this->plugin->getActivePlugins();

        if (isset($activePlugins['metador_map'])) {
            $this->frontendCommand->runMethod(
                'MetadorOlBridge',
                'showResults',
                $bboxParams,
                $response
            );
        }

        return $this;
    }

    #[Route('/heartbeat', name: 'app_heartbeat', methods: ['GET'])]
    public function heartbeat(): AjaxResponse
    {
        return new AjaxResponse([]);
    }

    #[Route('/process/{handle}', name: 'metador_process', methods: ['GET'])]
    public function processAction($handle): AjaxResponse
    {
        return new AjaxResponse([
            'process' => $this->process->isRunning($handle)
        ]);
    }

    protected function getSourceConfiguration(): array
    {
        $profileConfig = $this->getPluginConfiguration();
        $sourceConfig  = [];

        foreach ($this->source->all() as $source) {
            $sourceConfigProfiles = [];

            foreach ($profileConfig as $key => $profile) {
                if (is_array($profile['source']) && in_array($source->getSlug(), $profile['source'])) {
                    $sourceConfigProfiles[$key] = $profile['name'];
                }
            }

            $sourceConfig[$source->getSlug()] = [
                'name' => $source->getName(),
                'profiles' => $sourceConfigProfiles,
            ];
        }

        return $sourceConfig;
    }

    protected function getPluginConfiguration(): array
    {
        $profileConfig = [];

        foreach ($this->plugin->getActiveProfiles() as $key => $profile) {
            $configuration = $this->config->get('source', 'plugin', $key);

            $profileConfig[$key] = [
                'name'   => $profile['name'],
                'source' => is_null($configuration) ? [] : $configuration,
            ];
        }

        return $profileConfig;
    }
}
