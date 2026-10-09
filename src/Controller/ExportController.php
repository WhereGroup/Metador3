<?php

namespace App\Controller;

use App\Component\Exceptions\MetadataException;
use App\Component\PDFExport;
use App\Service\Address;
use App\Service\Core;
use App\Service\Metadata;
use App\Service\Plugin;
use App\Service\Search\JsonFilterReader;
use App\Service\Search\PropertyNameNotFoundException;
use App\Service\Search\SolrSearch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/export')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly SolrSearch $search,
        private readonly Metadata   $metadata,
        private readonly Address    $address,
    )
    {
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    #[Route('/xml/{id}', name: 'metador_export_xml', methods: ['GET'])]
    public function xml($id, Plugin $pluginService): Response
    {
        $p = $this->findObject($id);
        $this->denyAccessUnlessGranted('view', $p);

        return $this->xmlResponse(
            $this->metadata->getProcessor()->objectToXml($p, $pluginService->getPluginTemplatePath($p['_profile']) . '/Export/metadata.xml.twig')
        );
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    #[Route('/json/{id}', name: 'metador_export_json', methods: ['GET'])]
    public function jsonAction($id): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');
        $p = $this->findObject($id);
        $this->denyAccessUnlessGranted('view', $p);
        ksort($p);
        return new JsonResponse($p);
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    #[Route('"/obj/{id}', name: 'metador_export_obj', methods: ['GET'])]
    public function obj($id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');
        $p = $this->findObject($id);
        $this->denyAccessUnlessGranted('view', $p);
        ksort($p);
        return new Response('<pre>' . print_r($p, 1) . '</pre>');
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    #[Route('/pdf/{id}', name: 'metador_export_pdf', methods: ['GET'])]
    public function pdf($id, Plugin $plugin): void
    {
        $p = $this->findObject($id);
        $this->denyAccessUnlessGranted('view', $p);

        $html = $this->render($plugin->getPluginTemplatePath($p['_profile']) . '/Export/pdf.html.twig', [
            "p" => $p
        ]);

        error_reporting(E_ERROR);
        $pdf = new PDFExport('P', 'mm', 'A4', true, 'UTF-8', false, false);
        $pdf->setUUID($p['_uuid']);
        $pdf->createPdf($html->getContent(), $p);
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    #[Route('/html/{id}', name: 'metador_export_html', methods: ['GET'])]
    public function html($id, Core $core, Plugin $plugin): Response
    {
        $p = $this->findObject($id);
        $core->denyAccessUnlessGranted('view', $p);

        return $this->render($plugin->getPluginTemplatePath($p['_profile']) . "/Export/html.html.twig", [
            "p" => $p
        ]);
    }

    protected function xmlResponse($xml): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/xml');
        $response->setContent("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $xml);
        return $response;
    }

    /**
     * @throws MetadataException
     * @throws PropertyNameNotFoundException
     */
    protected function findObject($id): array
    {
        $response = $this->search
            ->setExpression(JsonFilterReader::read(['and' => ['eq' => ['id' => $id]]], $this->search->createExpression()))
            ->find();

        if (!isset($response['rows'][0])) {
            throw new MetadataException("Metadata not found!");
        }

        if (is_object($response['rows'][0])) {
            $p = $response['rows'][0]->object;
        } elseif (is_array($response['rows'][0])) {
            $p = $response['rows'][0]['object'];
        }

        if (!is_array($p) || !isset($p['_uuid']) || $p['_uuid'] != $id) {
            throw new MetadataException("Metadata not found!");
        }

//        return $p;
        $entity = $this->metadata->findById($id);
        Metadata::addAddressDetails($this->address, $entity);

        return $entity->getObject();
    }
}
