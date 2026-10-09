<?php

namespace App\Controller\Import;

use App\Entity\ImportExport;
use App\Form\ExportType;
use App\Form\ImportType;
use App\Repository\ImportExportRepository;
use App\Service\Import\ImportExport as ImportExportService;
use App\Service\Logger;
use App\Service\Process;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;

#[Route('/admin/import')]
class PluginController extends AbstractController
{
    public function __construct(private readonly Logger $logger)
    {
    }

    #[Route('/', name: 'metador_admin_import', methods: ['GET'])]
    public function indexAction(ImportExportRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        return $this->render(
            'import/Plugin/index.html.twig',
            [
                'rows' => $repo->findAll()
            ]
        );
    }

    #[Route('/run/import/{id}/{handle}', name: 'metador_admin_import_run', methods: ['POST'])]
    public function importAction($id, Process $process, $handle = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $handle = $process->runSymfonyCommandInBackground(
            'metador:import ' . (string)$id,
            $handle
        );

        return new JsonResponse(['handle' => $handle]);
    }

    #[Route('/run/export/{id}/{handle}', name: 'metador_admin_export_run', methods: ['POST'])]
    public function exportAction($id, Process $process, $handle = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $handle = $process->runSymfonyCommandInBackground('metador:export ' . $id, $handle);

        return new JsonResponse(['handle' => $handle]);
    }

    #[Route('/new/{operation}', name: 'metador_admin_import_new', methods: ['GET', 'POST'])]
    public function newImportAction(Request $request,EntityManagerInterface $em, string $operation): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this
            ->createForm(($operation === 'export') ? ExportType::class : ImportType::class, new ImportExport())
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ImportExport $entity */
            $entity = $form->getData();
            $entity->setToken();

            $em->persist($entity);
            $em->flush();
            $this->log('create', 'Job erfolgreich erstellt.', $entity->getId(), $entity);

            return $this->redirectToRoute('metador_admin_import');
        }

        return $this->render('import/Plugin/new.html.twig', [
            'form' => $form->createView(),
            'operation' => $operation
        ]);
    }

    #[Route('/edit/{operation}/{id}', name: 'metador_admin_import_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, ImportExportService $importExportService, EntityManagerInterface $em, $id, $operation = 'import'): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        /** @var ImportExport $entity */
        $entity = $importExportService->get($id);

        $form = $this
            ->createForm(($operation === 'export') ? ExportType::class : ImportType::class, $entity)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($entity);
            $em->flush();

            return $this->redirectToRoute('metador_admin_import');
        }

        return $this->render('import/Plugin/new.html.twig', [
            'form' => $form->createView(),
            'operation' => $operation
        ]);
    }

    #[Route('/confirm/{id}', name: 'metador_admin_import_confirm', methods: ['GET', 'POST'])]
    public function confirmAction(ImportExportService $importExportService, EntityManagerInterface $em, Request $request, $id): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this->createFormBuilder($importExportService->get($id))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $id     = $entity->getId();

            $em->remove($entity);
            $em->flush();
            $this->log('delete', 'Job erfolgreich gelöscht.', $id);

            return $this->redirectToRoute('metador_admin_import');
        }

        return $this->render('import/Plugin/confirm.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function log($operation, $message, $id, $entity = null): void
    {
        $log = $this->logger->newLog();
        $log->setType('success')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('importExport')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
        ;

        if ($entity !== null) {
            $log->setPath('metador_admin_import_edit', ['id' => $entity->getId()]);
        }

        $this->logger->set($log);
    }
}
