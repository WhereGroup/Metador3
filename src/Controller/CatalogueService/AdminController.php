<?php

namespace App\Controller\CatalogueService;

use App\Entity\Csw;
use App\Form\CswType;
use App\Service\Configuration;
use App\Service\CatalogueService\Csw as CswService;
use App\Service\Logger;
use App\Service\Source;
use App\Service\User;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

#[Route('/admin/csw')]
class AdminController extends AbstractController
{
    public function __construct(private readonly Logger $loggerService, private readonly User $userService)
    {}

    #[Route('/', name: 'metador_admin_csw', methods: ['GET'])]
    public function indexAction(CswService $cswService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('catalogueService/Admin/index.html.twig', [
            'services' => $cswService->all(),
        ]);
    }

    #[Route('/new', name: 'metador_admin_csw_new', methods: ['GET', 'POST'])]
    public function newAction(Configuration $configService, Request $request, CswService $cswService): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $cswDefaults = $configService->getValues('plugin', 'metador_catalogue_service');

        $form = $this
            ->createForm(CswType::class, (new Csw())->fromArray($cswDefaults))
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Csw $csw */
            $csw = $form->getData();
            if ($cswService->findOneBySlugAndSource($csw->getSlug(), $csw->getSource())) {
                $this->setFlash(
                    'warning',
                    'new',
                    '',
                    'Catalogue Service existiert bereits.',
                    []
                );

                return $this->redirectToRoute('metador_admin_csw');
            }

            $cswService->save($csw);

            $this->setFlash(
                'success',
                'new',
                $csw->getSlug(),
                'Catalogue Service %service% erfolgreich erstellt.',
                ['%service%' => $csw->getTitle()]
            );

            return $this->redirectToRoute('metador_admin_csw');
        }

        return $this->render('catalogueService/Admin/new.html.twig', [
            'action' => 'new',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{source}/{slug}', name: 'metador_admin_csw_edit', methods: ['GET', 'POST'])]
    public function editAction($source, $slug, CswService $cswService, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $cswInstance = $cswService->findOneBySlugAndSource($slug, $source);

        $form = $this
            ->createForm(CswType::class, $cswInstance)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Csw $entity */
            $entity = $form->getData();
            $cswService->save($entity);

            $this->setFlash(
                'success',
                'edit',
                $entity->getSlug(),
                'Catalogue Service %service% erfolgreich editiert.',
                ['%service%' => $entity->getTitle()]
            );

            return $this->redirectToRoute('metador_admin_csw');
        }

        return $this->render('catalogueService/Admin/new.html.twig', [
            'action' => 'edit',
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirm/{source}/{slug}', name: 'metador_admin_csw_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($source, $slug, CswService $cswService, Request $request, Source $sourceService): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $cswInstance = $cswService->findOneBySlugAndSource($slug, $source);
        $form = $this
            ->createFormBuilder($cswInstance)
            ->add('delete', SubmitType::class, ['label' => 'löschen'])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* @var Csw $entity */
            $entity = $form->getData();
            $id = $entity->getSlug();

            $sourceService->remove($entity);

            $this->setFlash(
                'success',
                'edit',
                $id,
                'Csw %csw% erfolgreich gelöscht.',
                ['%csw%' => $id]
            );

            return $this->redirectToRoute('metador_admin_csw');
        }

        return $this->render('catalogueService/Admin/confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function setFlash($type, $operation, $id, $message, $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType($type)
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('csw')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }
}
