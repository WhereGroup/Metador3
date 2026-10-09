<?php

namespace App\Controller;

use App\Component\Exceptions\MetadorException;
use App\Entity\Address;
use App\Event\AddressChangeEvent;
use App\Form\AddressType;
use App\Service\Address as AddressesService;
use App\Service\Configuration;
use App\Service\Logger;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/address')]
class AddressController extends AbstractController
{
    public function __construct(
        private readonly AddressesService $addressService,
        private readonly Configuration $configService,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly Logger $loggerService,
        private readonly User $userService,
    ) {
    }

    #[Route('/', name: 'metador_admin_address', methods: ['GET', 'POST'])]
    public function indexAction(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        return $this->render('address/index.html.twig', $this->addressService->search(
            $request->query->get('terms', ''),
            $request->query->get('page', 1),
            $this->configService
                ->get('popup_search_hits', 'plugin', 'metador_core', 5)
        ));
    }

    #[Route('/new', name: 'metador_admin_address_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this
            ->createForm(AddressType::class, new Address())
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            try {
                $this->addressService->save($entity);
            } catch (MetadorException $e) {
                $this->setFlashWarning(
                    'create',
                    '',
                    $e->getMessage(),
                    []
                );

                return $this->redirectToRoute('metador_admin_address');
            }

            $this->setFlashSuccess(
                'create',
                $entity->getId(),
                'Adresse %address% erfolgreich erstellt.',
                ['%address%' => $entity->getOrganisationName()]
            );

            return $this->redirectToRoute('metador_admin_address');
        }

        return $this->render('address/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_address_edit', methods: ['GET', 'POST'])]
    public function editAction($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $entity = $this->addressService->get($id);

        if (!$entity) {
            $this->setFlashWarning('edit', $id, 'Adresse nicht gefunden.', []);
            return $this->redirectToRoute('metador_admin_address');
        }

        $form = $this
            ->createForm(AddressType::class, $entity)
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            try {
                $this->addressService->save($entity);

                $this->setFlashSuccess(
                    'edit',
                    $id,
                    'Adresse %address% erfolgreich bearbeitet.',
                    ['%address%' => $entity->getOrganisationName()]
                );
            } catch (MetadorException $e) {
                $this->setFlashWarning(
                    'edit',
                    $id,
                    'Adresse %address% existiert bereits.',
                    ['%address%' => $entity->getOrganisationName()]
                );
            }

            return $this->redirectToRoute('metador_admin_address');
        }

        return $this->render('address/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/confirm/{id}', name: 'metador_admin_address_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this->createFormBuilder($this->addressService->get($id))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $name   = $entity->getOrganisationName();
            $id     = $entity->getId();
            $event  = new AddressChangeEvent($entity, []);
            $this->dispatcher->dispatch($event, 'address.pre_delete');
            $this->addressService->remove($entity);

            $this->setFlashSuccess(
                'edit',
                $id,
                'Adresse %address% erfolgreich gelöscht.',
                ['%address%' => $name]
            );

            return $this->redirectToRoute('metador_admin_address');
        }

        return $this->render('address/confirm.html.twig', [
            'form' => $form->createView()
        ]);
    }

    protected function setFlashWarning($operation, $id, $message, array $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType('warning')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('address')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }

    protected function setFlashSuccess($operation, $id, $message, array $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType('success')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('address')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->loggerService->set($log);

        unset($log);
    }
}
