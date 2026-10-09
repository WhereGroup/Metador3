<?php

namespace App\Controller;

use App\Entity\Source;
use App\Event\SourceEvent;
use App\Form\SourceType;
use App\Service\Logger;
use App\Service\Source as SourceService;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/source')]
class SourceController extends AbstractController
{
    public function __construct(
        private readonly SourceService $sourceService,
        private readonly Logger $loggerService,
        private readonly User $userService,
    )
    {}

    #[Route('/', name: 'metador_admin_source', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('source/index.html.twig', [
            'sources' => $this->sourceService->all()
        ]);
    }

    #[Route('/new/', name: 'metador_admin_source_new', methods: ['GET', 'POST'])]
    public function new(Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this
            ->createForm(SourceType::class, new Source())
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            if ($this->sourceService->findBySlug($entity->getSlug())) {
                $this->setFlashWarning(
                    'edit',
                    '',
                    'Datenquelle existiert bereits.',
                    []
                );

                return $this->redirectToRoute('metador_admin_source');
            }

            $this->sourceService->save($entity);

            $this->setFlashSuccess(
                'edit',
                $entity->getId(),
                'Datenquelle %source% erfolgreich erstellt.',
                ['%source%' => $entity->getName()]
            );

            return $this->redirectToRoute('metador_admin_source');
        }

        return $this->render('source/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_source_edit', methods: ['GET', 'POST'])]
    public function edit($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this
            ->createForm(SourceType::class, $this->sourceService->get($id))
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            $this->sourceService->save($entity);

            $this->setFlashSuccess(
                'edit',
                $id,
                'Datenquelle %source% erfolgreich bearbeitet.',
                ['%source%' => $entity->getName()]
            );

            return $this->redirectToRoute('metador_admin_source');
        }

        return $this->render('source/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/confirm/{id}', name: 'metador_admin_source_confirm', methods: ['GET', 'POST'])]
    public function confirm($id, Request $request, EventDispatcher $dispatcher): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this->createFormBuilder($this->sourceService->get($id))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])->getForm()->handleRequest($request);

        $event = new SourceEvent($form->getData()->getSlug());

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $name   = $entity->getName();
            $id     = $entity->getId();

            $dispatcher->dispatch($event, 'source.pre_delete');
            $this->sourceService->remove($entity);
            $dispatcher->dispatch($event, 'source.post_delete');

            $this->setFlashSuccess(
                'edit',
                $id,
                'Datenquelle %source% erfolgreich gelöscht.',
                ['%source%' => $name]
            );

            return $this->redirectToRoute('metador_admin_source');
        }

        $dispatcher->dispatch($event, 'source.confirm_delete');

        return $this->render('source/confirm.html.twig', [
            'form'     => $form->createView(),
            'messages' => $event->getMessages()
        ]);
    }

    private function setFlashWarning($operation, $id, $message, $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType('warning')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('source')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }

    private function setFlashSuccess($operation, $id, $message, $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType('success')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('source')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }
}
