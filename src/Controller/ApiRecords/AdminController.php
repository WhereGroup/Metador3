<?php

namespace App\Controller\ApiRecords;

use App\Service\Configuration;
use App\Service\Logger;
use App\Service\Source;
use App\Service\User;
use App\Entity\Records;
use App\Service\ApiRecords\Records as RecordsService;
use App\Form\RecordsType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api-records')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly Logger $loggerService,
        private readonly User $userService,
    )
    {
    }

    #[Route('/', name: 'metador_admin_api_records', methods: ['GET'])]
    public function indexAction(RecordsService $redordsService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('apiRecords/Admin/index.html.twig', [
            'services' => $redordsService->all(),
        ]);
    }

    #[Route('/new/', name: 'metador_admin_api_records_new', methods: ['GET', 'POST'])]
    public function newAction(Configuration $configService, Request $request, RecordsService $recordsService): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $defaults = $configService->getValues('plugin', 'metador_api_records');

        $form = $this
            ->createForm(RecordsType::class, (new Records())->fromArray($defaults))
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Records $entity */
            $entity = $form->getData();
            if ($recordsService->findOneBySlugAndSource($entity->getSlug(), $entity->getSource())) {
                $this->setFlash(
                    'warning', 'new', '', 'OGC API Records existiert bereits.', []
                );

                return $this->redirectToRoute('metador_admin_api_records');
            }

            $recordsService->save($entity);

            $this->setFlash(
                'success', 'new', $entity->getSlug(), 'OGC API Records %service% erfolgreich erstellt.', ['%service%' => $entity->getTitle()]
            );

            return $this->redirectToRoute('metador_admin_api_records');
        }

        return $this->render('apiRecords/Admin/new.html.twig', [
            'action' => 'new',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{source}/{slug}', name: 'metador_admin_api_records_edit', methods: ['GET', 'POST'])]
    public function editAction($source, $slug, RecordsService $recordsService, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $recordsInstance = $recordsService->findOneBySlugAndSource($slug, $source);

        $form = $this
            ->createForm(RecordsType::class, $recordsInstance)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Records $entity */
            $entity = $form->getData();
            $recordsService->save($entity);

            $this->setFlash(
                'success', 'edit', $entity->getSlug(), 'OGC API-Records %service% erfolgreich editiert.', ['%service%' => $entity->getTitle()]
            );

            return $this->redirectToRoute('metador_admin_api_records');
        }

        return $this->render('apiRecords/Admin/new.html.twig', [
            'action' => 'edit',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirm/{source}/{slug}', name: 'metador_admin_api_records_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($source, $slug, RecordsService $recordsService, Request $request, Source $sourceService): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $recordsInstance = $recordsService->findOneBySlugAndSource($slug, $source);
        $form = $this
            ->createFormBuilder($recordsInstance)
            ->add('delete', SubmitType::class, ['label' => 'löschen'])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Records $entity */
            $entity = $form->getData();
            $id = $entity->getSlug();

            $sourceService->remove($entity);

            $this->setFlash(
                'success', 'edit', $id, 'OGC API-Records %records% erfolgreich gelöscht.', ['%records%' => $id]
            );

            return $this->redirectToRoute('metador_admin_api_records');
        }

        return $this->render('apiRecords/Admin/confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function setFlash($type, $operation, $id, $message, $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType($type)
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('records')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }
}
