<?php

namespace App\Controller;

use App\Event\ManualEvent;
use App\Service\App;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[Route('/public/manual')]
class ManualController extends AbstractController
{

    public function __construct(
        #[Autowire('%kernel.environment%')]
        private string $env)
    {
    }

    #[Route('/{manual}', name: 'manual_index', methods: ['GET'])]
    public function indexAction(
        AuthorizationCheckerInterface $authorizationChecker,
        EventDispatcherInterface $eventDispatcher,
        string $manual = 'Anwenderhandbuch',
    ): Response
    {
        $event = new ManualEvent();
        $event
            ->setEnvironment($this->env)
            ->setAuthorizationChecker($authorizationChecker)
            ->setManualType($manual)
        ;

        $eventDispatcher->dispatch($event, 'manual.loading');

        return $this->render('manual/Manual/index.html.twig', [
            'manual' => $manual,
            'index'  => $event->getIndex($manual)
        ]);
    }

    #[Route('/{manual}/{plugin}/{page}', name: 'manual_page', methods: ['GET'])]
    public function pageAction($manual, $plugin, $page): Response
    {
//        return $this->render('@' . $plugin . '/Manual/' . $page . '.html.twig', [
//            'manual' => $manual
//        ]);
        return $this->render('manual/Manual/' . $page . '.html.twig', [
            'manual' => $manual
        ]);
    }
}
