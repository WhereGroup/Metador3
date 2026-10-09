<?php

namespace App\Controller;

use App\Component\Exceptions\MetadorException;
use App\Component\PictureTransformation;
use App\Entity\User;
use App\Form\ProfileType;
use App\Form\UserType;
use App\Service\Logger;
use App\Service\User as UserService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Logger $logger
    )
    {}

    #[Route('/admin/user/', name: 'metador_admin_user', methods: ['GET', 'POST'])]
    public function indexAction(): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $this->userService->findAll(),
        ]);
    }

    #[Route('/admin/user/new', name: 'metador_admin_user_new', methods: ['GET'])]
    public function newAction(): Response
    {
        return $this->render('user/new.html.twig', [
            'form' => $this
                ->createForm(UserType::class, new User())
                ->createView()
        ]);
    }

    #[Route('/admin/user/create', name: 'metador_admin_user_create', methods: ['POST'])]
    public function createAction(Request $request): RedirectResponse|Response
    {
        $user = new User();

        $form = $this
            ->createForm(UserType::class, $user)
            ->handleRequest($request);

        if ($user->getPicture() !== null) {
            $picture = new PictureTransformation($user->getPicture());
            $picture->resizeProfilePicture(60, 60);
            $user->setPicture($picture->getImageBase64Encode());
        }

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->userService->insert($user);
                $this->log('success', 'edit', $user->getId(), 'Benutzer %username% wurde erfolgreich erstellt.', [
                    '%username%' => $user->getUsername()
                ]);
            } catch (MetadorException $e) {
                $this->log('error', 'edit', $user->getId(), 'Benutzer %username% konnte nicht erstellt werden.', [
                    '%username%' => $user->getUsername()
                ]);
            }

            return $this->redirectToRoute('metador_admin_user');
        }

        return $this->render('user/new.html.twig', [
            'form'   => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_user_edit', methods: ['GET'])]
    public function editAction($id): RedirectResponse|Response
    {
        try {
            $user = $this->userService->get($id);
        } catch (MetadorException $e) {
            $this->log('error', 'edit', $id, 'Benutzer konnte nicht bearbeitet werden.');
            return $this->redirectToRoute('metador_admin_user');
        }

        return $this->render('user/new.html.twig', [
            'form' => $this
                ->createForm(UserType::class, $user)
                ->createView()
        ]);
    }

    #[Route('/admin/user/update/{id}', name: 'metador_admin_user_update', methods: ['POST'])]
    public function updateAction(Request $request, $id): RedirectResponse|Response
    {
        try {
            $user = $this->userService->get($id);

            $oldPassword = $user->getPassword();
            $oldPicture = $user->getPicture();

            $form = $this
                ->createForm(UserType::class, $user)
                ->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getPassword() != "" && $oldPassword != $user->getPassword()) {
                    $user->setPassword(
                        $this->userService->encodePassword($user, $user->getPassword())
                    );
                } else {
                    $user->setPassword($oldPassword);
                }

                if (!empty($user->getPicture()) &&  $oldPicture !== $user->getPicture()) {
                    $picture = new PictureTransformation($user->getPicture());
                    $picture->resizeProfilePicture(60, 60);
                    $user->setPicture($picture->getImageBase64Encode());
                } else {
                    $user->setPicture($oldPicture);
                }

                $this->userService->update($user);

                $this->log('success', 'update', $id, 'Benutzer %username% wurde erfolgreich bearbeitet.', [
                    '%username%' => $user->getUsername()
                ]);

                return $this->redirectToRoute('metador_admin_user');
            }
        } catch (MetadorException $e) {
            $this->log('error', 'update', $id, 'Benutzer %username% konnte nicht erstellt werden.', [
                '%username%' => $user->getUsername()
            ]);

            return $this->redirectToRoute('metador_admin_user');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/user/delete/{id}', name: 'metador_admin_user_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this->createFormBuilder($this->userService->get($id))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])
            ->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entity = $form->getData();
                $name   = $entity->getUsername();
                $id     = $entity->getId();

                $this->userService->delete($entity);
                $this->log('success', 'confirm', $id, 'Benutzer %username% erfolgreich gelöscht.', [
                    '%username%' => $name
                ]);
            } catch (Exception $e) {
                $this->log('error', 'confirm', $id, 'Benutzer %username% konnte nicht gelöscht werden.', [
                    '%username%' => $name
                ]);
            }

            return $this->redirectToRoute('metador_admin_user');
        }

        return $this->render('user/confirm.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/user/profile/{id}', name: 'metador_user_profile', methods: ['GET', 'POST'])]
    public function profileAction($id, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');


        try {
            $user = $this->userService->get($id);
            $oldPassword = $user->getPassword();

            $form = $this
                ->createForm(ProfileType::class, $user)
                ->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($user->getPassword() != "" && $oldPassword != $user->getPassword()) {
                    $user->setPassword(
                        $this->userService->encodePassword($user, $user->getPassword())
                    );
                } else {
                    $user->setPassword($oldPassword);
                }

                $this->userService->update($user);

                $this->log('success', 'update', $id, 'Kennwort für %username% wurde erfolgreich geändert.', [
                    '%username%' => $user->getUsername()
                ]);

                return $this->redirectToRoute('metador_home');
            }
        } catch (MetadorException $e) {
            $this->log('error', 'update', $id, 'Kennwort für %username% konnte nicht geändert werden.', [
                '%username%' => $user->getUsername()
            ]);

            return $this->redirectToRoute('metador_home');
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function log($type, $operation, $id, $message, $parameter = []): void
    {
        $log = $this->logger->newLog();

        $log->setType($type)
            ->setMessage($message, $parameter)
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('user')
            ->setOperation($operation)
            ->setIdentifier($id);

        if (!empty($id)) {
            $log->setPath('metador_admin_user_edit', ['id' => $id]);
        }

        $this->logger->set($log);

        unset($log);
    }
}
