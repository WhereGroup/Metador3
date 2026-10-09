<?php

namespace App\Controller;

use App\Entity\Page;
use App\Form\PageEditType;
use App\Form\PageType;
use App\Service\Configuration;
use Exception;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    public function __construct(private readonly Configuration $configService)
    {
    }

    #[Route('/admin/page', name: 'metador_admin_page', methods: ['GET', 'POST'])]
    public function indexAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');
        $pages = [];

        foreach ($this->configService->getAll('pages') as $config) {
            $pages[] = Page::fromArray($config['value']);
        }

        return $this->render("page/index.html.twig", [
            'pages' => $pages
        ]);
    }

    #[Route('/admin/page/new/{slug}', name: 'metador_admin_page_new', methods: ['GET', 'POST'])]
    public function newAction(Request $request, $slug = ""): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $page = new Page();
        $page->setSlug($slug);

        $form = $this
            ->createForm(PageType::class, $page)
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Page $entity */
            $entity = $form->getData();
            $this->configService->set($entity->getSlug(), $entity->toArray(), 'pages');
            return $this->redirectToRoute('metador_admin_page');
        }

        return $this->render('page/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/page/edit/{slug}', name: 'metador_admin_page_edit', methods: ['GET', 'POST'])]
    public function editAction(Request $request, $slug): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this
            ->createForm(PageEditType::class, $this->getEntity($slug))
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Page $entity */
            $entity = $form->getData();
            $this->configService->set($entity->getSlug(), $entity->toArray(), 'pages');
            return $this->redirectToRoute('metador_admin_page');
        }

        return $this->render('page/edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/admin/page/delete/{slug}', name: 'metador_admin_page_delete', methods: ['GET', 'POST'])]
    public function deleteAction(Request $request, $slug)
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $form = $this->createFormBuilder($this->getEntity($slug))
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])->getForm()->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->configService->remove($slug, 'pages');
            return $this->redirectToRoute('metador_admin_page');
        }

        return $this->render('page/delete.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/public/page/show/{slug}', name: 'metador_page_show', methods: ['GET'])]
    public function showAction($slug): Response
    {
        try {
            $page = $this->getEntity($slug);
            if (!empty($page->getLink())) {
                return $this->redirect($page->getLink());
            }
        } catch (Exception $e) {
            $page = new Page();
        }
        return $this->render('page/show.html.twig', [
            'page' => $page,
            'slug' => $slug
        ]);
    }

    private function getEntity($slug): Page
    {
        $data = $this->configService->get($slug, 'pages');

        if (is_null($data)) {
            throw new RuntimeException("Slug nicht gefunden.");
        }

        return Page::fromArray($data);
    }
}
