<?php

namespace App\Controller;

use App\Entity\Keyword;
use App\Form\KeywordType;
use App\Repository\KeywordRepository;
use App\Service\Logger;
use App\Service\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @Route("/admin/keyword")
 */
#[Route('/admin/keyword')]
class KeywordController extends AbstractController
{
    public function __construct(private readonly Logger $logger, private readonly User $userService)
    {
    }

    #[Route('/', name: 'metador_admin_keyword', methods: ['GET'])]
    public function indexAction(KeywordRepository $keywordRepository): Response
    {
        return $this->render('basicProfile/Keyword/index.html.twig', [
            'keywords' => $keywordRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'metador_admin_keyword_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, EntityManagerInterface $entityManager): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this
            ->createForm(KeywordType::class, new Keyword())
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Keyword $entity */
            $entity = $form->getData();
            $entity->setKeywords(array_combine($entity->getKeywords(), $entity->getKeywords()));

            $entityManager->persist($entity);
            $entityManager->flush();

            $this->setFlashSuccess('create', $entity->getId(), "Keyword erfolgreich erstellt.");

            return $this->redirectToRoute('metador_admin_keyword');
        }

        return $this->render('basicProfile/Keyword/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_keyword_edit', methods: ['GET', 'POST'])]
    public function editAction($id, Request $request, EntityManagerInterface $entityManager, KeywordRepository $keywordRepository): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $entity = $keywordRepository->findOneById($id);

        $form = $this
            ->createForm(KeywordType::class, $entity)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $entity->setKeywords(array_combine($entity->getKeywords(), $entity->getKeywords()));

            $entityManager->persist($entity);
            $entityManager->flush();

            $this->setFlashSuccess('update', $entity->getId(), "Keyword erfolgreich bearbeitet.");

            return $this->redirectToRoute('metador_admin_keyword');
        }

        return $this->render('basicProfile/Keyword/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirm/{id}', name: 'metador_admin_keyword_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Request $request, EntityManagerInterface $entityManager, KeywordRepository $keywordRepository): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $entity = $keywordRepository->findOneById($id);


        $form = $this->createFormBuilder($entity)
            ->add('delete', SubmitType::class, [
                'label' => 'löschen',
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            $entityManager->remove($entity);
            $entityManager->flush();

            $this->setFlashSuccess('delete', $entity->getId(), "Keyword erfolgreich gelöscht.");

            return $this->redirectToRoute('metador_admin_keyword');
        }

        return $this->render('basicProfile/Keyword/confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function setFlashWarning($operation, $id, $message, $parameter = []): void
    {
        $log = $this->logger->newLog();

        $log->setType('warning')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('keyword')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->logger->set($log);

        unset($log);
    }

    private function setFlashSuccess($operation, $id, $message, $parameter = []): void
    {
        $log = $this->logger->newLog();

        $log->setType('success')
            ->setFlashMessage()
            ->setCategory('application')
            ->setSubcategory('keyword')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message)
            ->setMessageParameter($parameter)
            ->setUsername($this->userService->getUsernameFromSession());

        $this->logger->set($log);

        unset($log);
    }
}
