<?php

namespace App\Controller\Map;

use App\Entity\Wms;
use App\Form\WmsEditType;
use App\Form\WmsNewType;
use App\Service\Map\Map;
use App\Service\Source;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/map')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'metador_admin_map', methods: ['GET'])]
    public function indexAction(Map $map): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        return $this->render('map/Admin/index.html.twig', [
            'rows' => $map->all(),
        ]);
    }

    #[Route('/new/', name: 'metador_admin_map_new', methods: ['GET', 'POST'])]
    public function newAction(Map $map, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');
        $wms = new Wms();
        $wms->setTitle(Wms::TITLE_DEFAULT); // set Title
        $form = $this
            ->createForm(WmsNewType::class, $wms)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();

            try {
                $map->update($entity->getGcUrl(), $entity);
                $map->save($entity);

                return $this->redirectToRoute('metador_admin_map');
            } catch (Exception $e) {
                //$this->log('error', 'create', $e->getMessage());
            }
        }

        return $this->render('map/Admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'metador_admin_map_edit', methods: ['GET', 'POST'])]
    public function editAction($id, Map $map, Request $request): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this
            ->createForm(WmsEditType::class, $map->get($id))
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $map->save($form->getData());

            return $this->redirectToRoute('metador_admin_map');
        }

        return $this->render('map/Admin/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/confirm/{id}', name: 'metador_admin_map_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($id, Map $map, Request $request, Source $source): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this->createFormBuilder($map->get($id))
            ->add('delete', SubmitType::class, ['label' => 'löschen'])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $form->getData();
            $source->remove($entity);
            return $this->redirectToRoute('metador_admin_map');
        }

        return $this->render('map/Admin/confirm.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
