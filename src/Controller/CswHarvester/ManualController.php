<?php

namespace App\Controller\CswHarvester;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/manual')]
class ManualController extends AbstractController
{
    #[Route('/csw/harvester', name: 'manual_harvester', methods: ['GET'])]
    public function cswAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        return $this->render('cswHarvester/Manual/csw-harvester.html.twig');
    }
}
