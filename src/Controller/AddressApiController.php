<?php

namespace App\Controller;

use App\Component\AjaxResponse;
use App\Service\Address;
use App\Service\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/address/api')]
class AddressApiController extends AbstractController
{
    #[Route('/all', name: 'metador_address_api_all', methods: ['GET', 'POST'])]
    public function allAction(Request $request, Address $addressService, Configuration $configService): AjaxResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');

        $result = $addressService->search(
            $request->query->get('terms'),
            $request->query->get('page'),
            $configService
                ->get('popup_search_hits', 'plugin', 'metador_core', 5)
        );

        return new AjaxResponse([
            'data' => $result['rows'],
            'html' => $this->renderView('api/all.html.twig', $result)
        ]);
    }
}
