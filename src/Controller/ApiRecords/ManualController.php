<?php

namespace App\Controller\ApiRecords;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/manual')]
class ManualController extends AbstractController
{
    #[Route('/apirecords', name: 'manual_api_records', methods: ['GET'])]
    public function apiRecordsAction()
    {
//        die("asdf");
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        return $this->render('apiRecords/Manual/apirecords.html.twig');
    }
}
