<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Service\Logger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/group')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Logger $loggerService,
        private readonly GroupRepository $groupRepository,
    ){}

    #[Route('/', name: 'metador_admin_group', methods: ['GET', 'POST'])]
    public function indexAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('group/index.html.twig', [
            'groups' => $this->groupRepository->findAllSorted()
        ]);
    }

    #[Route('/new', name: 'metador_admin_group_new', methods: ['GET'])]
    public function newAction(UserRepository $userRepo): Response
    {
        return $this->render('group/new.html.twig', [
            'form' => $this
                ->createForm(GroupType::class, new Group())
                ->createView(),
            'users' => $userRepo->findAll()
        ]);
    }

    #[Route('/create', name: 'metador_admin_group_create', methods: ['POST'])]
    public function createAction(Request $request): RedirectResponse
    {
        $entity  = new Group();
        $form = $this->createForm(GroupType::class, $entity)->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roleExists = $this->groupRepository->findOneBy(['role' => $entity->getRole()]);

            if (!$roleExists) {
                $this->em->persist($entity);
                $this->em->flush();
                $this->log('success', 'create', $entity->getId(), 'Gruppe erfolgreich hinzugefügt.');
            } else {
                $this->log('warning', 'create', $entity->getId(), 'Gruppe existiert bereits.');
            }
        }

        return $this->redirect($this->generateUrl('metador_admin_group'));
    }

    #[Route('/edit/{id}', name: 'metador_admin_group_edit', methods: ['GET'])]
    public function editAction($id, UserRepository $userRepo): Response
    {
        return $this->render('group/new.html.twig', [
            'form' => $this
                ->createForm(GroupType::class, $this->getGroup($id))
                ->createView(),
            'users' => $userRepo->findAll()
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_group_update', methods: ['POST'])]
    public function updateAction($id, Request $request): Response
    {
        $form = $this
            ->createForm(GroupType::class, $this->getGroup($id))->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $group = $form->getData();
            $this->em->persist($group);
            $this->em->flush();

            return $this->redirectToRoute('metador_admin_group');
        }

        return $this->render('group/new.html.twig', ['form' => $form]);
    }

    #[Route('/delete/{id}', name: 'metador_admin_group_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this->createFormBuilder($this->groupRepository->findOneById($id))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $name   = $entity->getRole();
            $id     = $entity->getId();

            $this->em->remove($entity);
            $this->em->flush();

            $this->log(
                'success',
                'delete',
                $id,
                'Gruppe %group% erfolgreich gelöscht.',
                ['%group%' => $name]
            );

            return $this->redirectToRoute('metador_admin_group');
        }

        return $this->render('group/confirm.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function getGroup($id): mixed
    {
        $entity = $this->groupRepository->findOneById($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Group entity.');
        }

        return $entity;
    }

    private function log($type, $operation, $id, $message, $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType($type)
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('group')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message, $parameter);

        $this->loggerService->set($log);

        unset($log);
    }
}
