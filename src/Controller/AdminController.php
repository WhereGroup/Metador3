<?php

namespace App\Controller;

use App\Service\Cache;
use App\Service\HealthCheck;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'metador_admin_index', methods: ['GET', 'POST'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');
        return $this->render("admin/index.html.twig");
    }

    #[Route('/health', name: 'metador_admin_health', methods: ['GET', 'POST'])]
    public function healthCheck(HealthCheck $healthCheck): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $event = $healthCheck->check();
        return $this->render("admin/health.html.twig", [
            'hasError'   => $event->hasError(),
            'errorCount' => $event->getErrorCount(),
            'logs'       => $event->getLogs()
        ]);
    }

    #[Route('/clear/cache', name: 'metador_admin_clear_cache', methods: ['POST'])]
    public function clearCache(Cache $cache): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
        $cache->truncate();
        return $this->redirectToRoute('metador_admin_index');
    }
}
