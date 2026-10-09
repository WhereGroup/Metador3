<?php

namespace App\Controller\Import;

use App\Service\Import\WebServiceImport;
use App\Service\Logger;
use App\Service\Metadata;
use App\Service\Plugin;
use App\Service\Source;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/import')]
class ImportController extends AbstractController
{
    public function __construct(private readonly Logger $logger, private readonly User $userService)
    {
    }

    #[Route('/', name: 'metador_import_index', methods: ['GET'])]
    public function indexAction(Source $sourceService, Plugin $pluginService): Response
    {
        return $this->render('import/Import/index.html.twig', [
            'sources'  => $sourceService->all(),
            'profiles' => $pluginService->getActiveProfiles()
        ]);
    }

    #[Route('/capabilities', name: 'webservice_url_import', methods: ['POST'])]
    public function webserviceUrlImportAction(Request $request, Metadata $metadataService, WebServiceImport $webServiceImport): RedirectResponse
    {
        $source = $request->request->get('source');
        $profile = $request->request->get('profile');

        // Parse URL's
        $urls = $webServiceImport->parseGetCapabilitiesUrls(
            $request->request->get('urls', '')
        );

        if (!$urls) {
            $this->setFlashError('wmsurlimport', 'Bitte mindestens eine GetCapabilities URL angeben.');

            return $this->redirectToRoute('metador_admin_import');
        }

        foreach ($urls as $url) {
            if (!($p = $webServiceImport->isGetCapabilitiesUrl($url))) {
                $this->setFlashError(
                    'wmsurlimport',
                    $url . 'ist keine gültige URL bzw. liefert keine gültige WMS GetCapabilities XML.'
                );
                continue;
            }

            $uuid = $webServiceImport->convertUrlToUuid($url);

            $p['_uuid'] = $uuid;
            $p['_origin'] = 'wms-wfs-import';
            $p['fileIdentifier'] = $uuid;
            $p['identifierCode'] = $uuid;
            $p['identifierCodespace'] = "";
            $p['dateStamp'] = date('Y-m-d');
            $p['hierarchyLevel'] = 'service';
            try {
                $metadata = $metadataService->findById($uuid);
            } catch (\Exception $e) {
                $metadata = null;
            }

            if ($metadata) {
                $metadataService->updateByObject($metadata->getObject(), [
                    'source'  => $source,
                    'profile' => $profile
                ]);

                return $this->redirectToRoute('metador_admin_import');
            }

            $metadataService->insertByObject($p, [
                'source'  => $source,
                'profile' => $profile
            ]);
        }

        return $this->redirectToRoute('metador_admin_import');
    }

    private function setFlashError(string $operation, string $message, array $parameter = []): void
    {
        $log = $this->logger->newLog();

        $log->setType('error')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('import')
            ->setOperation($operation)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->logger->set($log);

        unset($log);
    }
}
