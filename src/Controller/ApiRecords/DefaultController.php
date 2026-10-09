<?php

namespace App\Controller\ApiRecords;

use App\Component\ApiRecords\Capabilities;
use App\Component\ApiRecords\OgcApiException;
use App\Component\ApiRecords\Query;
use App\Entity\Records;
use App\Service\ApiRecords\Records as RecordsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ApiRecords
 * @package App\Subscriber\ApiREcords\Controller
 */
#[Route('/apirecords/')]
class DefaultController extends AbstractController
{
    private string $apiConfigDir;
//    private string $apiTemplateDir;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly RecordsService        $recordsService
    )
    {
        $this->apiConfigDir = $this->parameterBag->get('kernel.project_dir') . '/config/app/apirecords/';
//        $this->apiTemplateDir = $this->parameterBag->get('kernel.project_dir') . '/templates/config/apirecords/';
    }

    protected function getRouteMapping(): array
    {
        return [
            'IDX' => 'apirecords_index',
            'CONFORMANCE' => 'apirecords_conformance',
            'COLLECTIONS' => 'apirecords_collections',
            'COLLECTION_IDX' => 'apirecords_collections_id',
            'ITEMS' => 'apirecords_collections_id_items',
            'ITEM_IDX' => 'apirecords_collections_id_items_id',
            'QUERYABLES' => 'apirecords_collections_id_queryables',
            'SORTABLES' => 'apirecords_collections_id_sortables',
            'FUNCTIONS' => 'apirecords_collections_id_functions',
        ];
    }

    protected function getContent(string $configFile): array
    {
//        try {
//            return Yaml::parse(file_get_contents($this->apiTemplateDir . $configFile));
//        } catch (\Throwable $e) {
            return Yaml::parse(file_get_contents($this->apiConfigDir . $configFile));
//        }
    }

    protected function getQueryables(): array
    {
        return $this->getContent('queryables.yaml');
    }

    protected function getQueryablesMap(): array
    {
        return $this->getContent('queryablesMap.yaml');
    }

    protected function getSortables(): array
    {
        return $this->getContent('sortables.yaml');
    }

    protected function getSortablesMap(): array
    {
        return $this->getContent('sortablesMap.yaml');
    }

    protected function getFunctions(): array
    {
        return $this->getContent('functions.yaml');
    }

    protected function getRecordsConfig(string $source, string $slug)
    {
        $record = $this->recordsService->findOneBySlugAndSource($slug, $source);
        if (null === $record) {
            throw new OgcApiException(OgcApiException::NOTFOUND);
        }
        return $record;
    }

    protected function getRouteParams(string $source, string $slug, array $getParams = []): array
    {
        return array_merge(
            [
                'source' => $source, # $record->getSource(),
                'slug' => $slug # $record->getSlug(),
            ],
            $getParams);
    }

    protected function getFormat(Request $request, array $acceptableFormats, string $defaultFormat = null): string
    {
        $format = $request->query->get('f');
        if (in_array($format, $acceptableFormats, true)) {
            return $format;
        } elseif ($format === null && $defaultFormat) {
            return $defaultFormat;
        }
        throw new OgcApiException(OgcApiException::BADREQUEST);
    }

    protected function getResponse(array $doc, string $format): Response //, $template = null, array $nav = null): Response
    {
        if ($format === Capabilities::FORMAT_JSON) {
            return new JsonResponse($doc);
        } elseif ($format === Capabilities::FORMAT_GEOJSON || $format === Capabilities::FORMAT_JSONSCHEMA) {
            $response = new JsonResponse($doc);
            $response->headers->set('Content-Type', $format);
            return $response;
        } elseif ($format === Capabilities::FORMAT_YAML) {
            $response = new Response(Yaml::dump($doc, 8, 2));
            $response->headers->set('Content-Type', Capabilities::FORMAT_YAML);
            return $response;
        }
        throw new OgcApiException(OgcApiException::BADREQUEST);
    }

    protected function getErrorResponse(\Throwable $e, int $statusCode = null): JsonResponse
    {
        return new JsonResponse(
            Capabilities::error($e->getCode(), $e->getMessage()),
            $statusCode ?: ($e instanceof OgcApiException ? $e->getHttpStatusCode() : JsonResponse::HTTP_INTERNAL_SERVER_ERROR)
        );
    }

    /**
     */
    #[Route('{source}/{slug}', name: 'apirecords_index', methods: ['GET'])]
    public function index(Request $request, string $source, string $slug, RouterInterface $router): JsonResponse|Response
    {
        try {
            $capabilities = new Capabilities(
                $this->getRecordsConfig($source, $slug),
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getIndexFormats(), Capabilities::FORMAT_JSON);
            $doc = $capabilities->generateIndex($format);

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/conformance', name: 'apirecords_conformance', methods: ['GET'])]
    public function conformance(Request $request, string $source, string $slug, RouterInterface $router): JsonResponse|Response
    {
        try {
            $capabilities = new Capabilities(
                $this->getRecordsConfig($source, $slug),
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getConformanceFormats(), Capabilities::FORMAT_JSON);

            $doc = $capabilities->generateConformance($format,
                Yaml::parse(file_get_contents($this->apiConfigDir . 'conformsTo.yaml'))
            );

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections', name: 'apirecords_collections', methods: ['GET'])]
    public function collectionsAction(Request $request, string $source, string $slug, RouterInterface $router): JsonResponse|Response
    {
        // Listet alle verfügbaren Collections auf, z. B. Metadaten-Kataloge.
        try {
            $capabilities = new Capabilities(
                $this->getRecordsConfig($source, $slug),
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getConformanceFormats(), Capabilities::FORMAT_JSON);
            $doc = $capabilities->generateCollections($format);

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}', name: 'apirecords_collections_id', methods: ['GET'])]
    public function collectionAction(Request $request, string $source, string $slug, string $collectionId, RouterInterface $router): JsonResponse|Response
    {
        // Details zu einer bestimmten Collection (z. B. Beschreibung, Umfang, etc.).
        try {
            /* @var Records $recordsConfig */
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getConformanceFormats(), Capabilities::FORMAT_JSON);
            $doc = $capabilities->generateCollection($recordsConfig->getSlug(), $recordsConfig->getTitle(), $recordsConfig->getAbstract(), $format);

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}/queryables', name: 'apirecords_collections_id_queryables', methods: ['GET'])]
    public function collectionQueryablesAction(Request $request, string $source, string $slug, string $collectionId, RouterInterface $router): JsonResponse|Response
    {
        // Details zu einer bestimmten Collection (z. B. Beschreibung, Umfang, etc.).
        try {
            /* @var Records $recordsConfig */
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getQueryablesFormats(), Capabilities::FORMAT_JSONSCHEMA);
            $doc = $capabilities->generateQueryables($this->getQueryables(), $recordsConfig->getSlug());

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}/sortables', name: 'apirecords_collections_id_sortables', methods: ['GET'])]
    public function collectionSortablesAction(Request $request, string $source, string $slug, string $collectionId, RouterInterface $router): JsonResponse|Response
    {
        // Details zu einer bestimmten Collection (z. B. Beschreibung, Umfang, etc.).
        try {
            /* @var Records $recordsConfig */
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getSortablesFormats(), Capabilities::FORMAT_JSONSCHEMA);
            $doc = $capabilities->generateSortables($this->getSortables(), $recordsConfig->getSlug());

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}/functions', name: 'apirecords_collections_id_functions', methods: ['GET'])]
    public function collectionFunctionsAction(Request $request, string $source, string $slug, string $collectionId, RouterInterface $router): JsonResponse|Response
    {
        // Details zu einer bestimmten Collection (z. B. Beschreibung, Umfang, etc.).
        try {
            /* @var Records $recordsConfig */
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getFunctionsFormats(), Capabilities::FORMAT_JSON);
//            $doc = $capabilities->generateFunctions($this->getFunctions(), $recordsConfig->getSlug());
            $doc = $this->getFunctions();

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}/items', name: 'apirecords_collections_id_items', methods: ['GET'])]
    public function itemsAction(Request $request, string $source, string $slug, string $collectionId, RouterInterface $router): JsonResponse|Response
    {
        // Liefert eine Liste von Records (z.B. ISO 19115 Metadaten).
        // Unterstützt Filter- und Paginierungsparameter.
        // Response: GeoJSON oder JSON.
        try {
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getItemFormats(), Capabilities::FORMAT_JSON);
            $query = new Query($recordsConfig, $request, $format, $this->getQueryables(), $this->getQueryablesMap(), $this->getSortables(), $this->getSortablesMap(), $this->getFunctions());
            $doc = $this->recordsService->getRecords($recordsConfig, $query, $capabilities);

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }

    /**
     */
    #[Route('{source}/{slug}/collections/{collectionId}/items/{itemId}', name: 'apirecords_collections_id_items_id', methods: ['GET'])]
    public function itemAction(Request $request, $source, $slug, $collectionId, $itemId, RouterInterface $router): JsonResponse|Response
    {
        // Detailinformationen zu einem bestimmten Record.
        try {
            $recordsConfig = $this->getRecordsConfig($source, $slug);
            $capabilities = new Capabilities(
                $recordsConfig,
                $router,
                $this->getRouteParams($source, $slug),
                $this->getRouteMapping()
            );
            $format = $this->getFormat($request, $capabilities->getItemFormats(), Capabilities::FORMAT_JSON);
            $doc = $this->recordsService->getRecordById($recordsConfig, $itemId, $this->getQueryablesMap());

            return $this->getResponse($doc, $format);
        } catch (\Exception $e) {
            return $this->getErrorResponse($e);
        }
    }
}
