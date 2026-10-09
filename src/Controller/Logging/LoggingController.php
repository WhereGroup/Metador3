<?php

namespace App\Controller\Logging;

use App\Component\AjaxResponse;
use App\Entity\Log;
use App\Repository\LogRepository;
use App\Service\Configuration;
use App\Service\Logger;
use App\Service\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/logging')]
class LoggingController extends AbstractController
{
    #[Route('/', name: 'metador_admin_logging', methods: ['GET'])]
    public function indexAction(Request $request, Configuration $configuration, LogRepository $logging): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $limit = $configuration->get('log_paging', 'plugin', 'metador_logging', 10);

        return $this->render('logging/Logging/index.html.twig', [
            'response'    => $logging->getLogs($request->query->get('page', 1), $limit),
            'types'       => $logging->getOptions('type'),
            'username'    => $logging->getOptions('username'),
            'category'    => $logging->getOptions('category'),
            'subcategory' => $logging->getOptions('subcategory'),
            'operation'   => $logging->getOptions('operation'),
            'source'      => $logging->getOptions('source')
        ]);
    }

    #[Route('/ajax', name: 'metador_admin_logging_filter', methods: ['GET'])]
    public function ajaxAction(Request $request, LogRepository $logging): AjaxResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $logs = $logging
            ->getLogs($request->query->get('page', 1), 10, [
                'date' => $request->query->get('date'),
                'type' => $request->query->get('type'),
                'category' => $request->query->get('category'),
                'subcategory' => $request->query->get('subcategory'),
                'operation' => $request->query->get('operation'),
                'username' => $request->query->get('username'),
                'orderby' => $request->query->get('orderby'),
                'source' => $request->query->get('source')
            ]);

        return new AjaxResponse([
            'response' => $logs,
            'template' => $this->renderView(
                'logging/Logging/table-results.html.twig',
                ['response' => $logs]
            )
        ]);
    }

    #[Route('/confirm', name: 'metador_admin_logging_confirm', methods: ['GET', 'POST'])]
    public function confirmAction(Request $request, LogRepository $logging, Logger $logger, User $userService): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        $form = $this->createFormBuilder(new Log())
            ->add('delete', SubmitType::class, [
                'label' => 'löschen'
            ])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logging->truncate();

            // Add log
            $log = $logger->newLog();

            $log->setType('success')
                ->setFlashMessage(true)
                ->setCategory('system')
                ->setSubcategory('logging')
                ->setOperation('truncate')
                ->setIdentifier('')
                ->setMessage('Logs erfolgreich geleert')
                ->setUsername($userService->getUsernameFromSession());

            $logger->set($log);

            return $this->redirectToRoute('metador_admin_logging');
        }

        return $this->render('logging/Logging/confirm.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
