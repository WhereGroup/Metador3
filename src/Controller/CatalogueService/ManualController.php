<?php

namespace App\Controller\CatalogueService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/manual')]
class ManualController extends AbstractController
{
    #[Route('/csw', name: 'manual_csw', methods: ['GET'])]
    public function cswAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        return $this->render('catalogueService/Manual/csw.html.twig');
    }
}
