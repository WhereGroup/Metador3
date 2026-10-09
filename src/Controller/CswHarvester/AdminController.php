<?php

namespace App\Controller\CswHarvester;

use App\Component\AjaxResponse;
use App\Component\CswHarvester\GetRecordsClient;
use App\Entity\CswHarvester;
use App\Form\CswHarvesterType;
use App\Repository\CswHarvesterRepository;
use App\Service\Browser;
use App\Service\Process;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/csw/harvester')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'metador_admin_harvester', methods: ['GET'])]
    public function indexAction(CswHarvesterRepository $cswHarvesterRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('cswHarvester/Admin/index.html.twig', [
            'rows' => $cswHarvesterRepository->findAll()
        ]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/new', name: 'metador_admin_harvester_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, EntityManagerInterface $em): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this
            ->createForm(CswHarvesterType::class, new CswHarvester())
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($form->getData());
            $em->flush();
            $em->clear();

            return $this->redirectToRoute('metador_admin_harvester');
        }

        return $this->render('cswHarvester/Admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/hits/{id}', name: 'metador_admin_harvester_hits', methods: ['GET'])]
    public function hitsAction($id, CswHarvesterRepository $cswHarvesterRepository, Browser $browser): AjaxResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        /** @var CswHarvester $entity */
        $entity = $cswHarvesterRepository->findOneById($id);

        return new AjaxResponse([
            'hits' => GetRecordsClient::getHits($browser, $entity->getUrl())
        ]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/edit/{id}', name: 'metador_admin_harvester_edit', methods: ['GET', 'POST'])]
    public function editAction($id, Request $request, CswHarvesterRepository $cswHarvesterRepository, EntityManagerInterface $em): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $entity = $cswHarvesterRepository->findOneById($id);

        $form = $this
            ->createForm(CswHarvesterType::class, $entity)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($form->getData());
            $em->flush();
            $em->clear();

            return $this->redirectToRoute('metador_admin_harvester');
        }

        return $this->render('cswHarvester/Admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/confirm/{id}', name: 'metador_admin_harvester_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Request $request, CswHarvesterRepository $cswHarvesterRepository, EntityManagerInterface $em): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $entity = $cswHarvesterRepository->findOneById($id);

        $form = $this
            ->createFormBuilder($entity)
            ->add('delete', SubmitType::class, ['label' => 'löschen'])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->remove($form->getData());
            $em->flush();
            $em->clear();

            return $this->redirectToRoute('metador_admin_harvester');
        }

        return $this->render('cswHarvester/Admin/confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/run/{id}/{handle}', name: 'metador_admin_harvester_run', methods: ['POST'])]
    public function runAction(Process $process, $id, $handle = null): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $process->sendResponse(json_encode([
            'handle' => $handle
        ]));

        $process->runSymfonyCommandInBackground(
            'metador:harvest:csw ' . (string)$id,
            $handle
        );

        return new Response();
    }
}
