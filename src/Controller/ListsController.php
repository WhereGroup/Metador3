<?php

namespace App\Controller;

use App\Service\Configuration;
use App\Service\Plugin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/lists')]
class ListsController extends AbstractController
{
    #[Route('/', name: 'metador_admin_lists', methods: ['GET'])]
    public function indexAction(Plugin $plugin, Configuration $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $lists = [];

        foreach ($plugin->getActiveProfiles() as $profileKey => $profile) {
            $lists[$profileKey] = $config->getAll('list-option', $profileKey);
        }

        return $this->render('basicProfile/Lists/index.html.twig', [
            'lists' => $lists
        ]);
    }

    #[Route('/show/{profile}/{key}', name: 'metador_admin_lists_show', methods: ['GET', 'POST'])]
    public function showAction($profile, $key, Configuration $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        return $this->render('basicProfile/Lists/show.html.twig', [
            'listProfile'  => $profile,
            'listKey'      => $key,
            'list'         => $config->get($key, 'list-option', $profile)
        ]);
    }

    #[Route('/new/{profile}/{key}', name: 'metador_admin_lists_new', methods: ['GET', 'POST'])]
    public function newAction($profile, $key, Request $request, Configuration $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        if ($request->getMethod() === 'POST'
            && !is_null($request->request->get('key', null))
            && !is_null($request->request->get('value', null))) {
            $value = $config->get($key, 'list-option', $profile, []);

            if ($request->request->get('prev_key') !== $request->request->get('key')
                && $request->request->get('prev_key') !== null) {
                unset($value[$request->request->get('prev_key')]);
            }

            $value[$request->request->get('key')] = $request->request->get('value');

            $config->set($key, $value, 'list-option', $profile);

            return $this->redirectToRoute('metador_admin_lists_show', ['profile' => $profile, 'key' => $key]);
        }

        return $this->render('basicProfile/Lists/new.html.twig', [
            'listProfile'  => $profile,
            'listKey'      => $key,
            'listTemplate' => $config->get($key, 'list-option-template', $profile),
        ]);
    }

    #[Route('/confirm/element/{profile}/{key}/{elementKey}', name: 'metador_admin_lists_confirm_element', defaults: ['elementKey' => ''], methods: ['GET', 'POST'])]
    public function confirmElementAction($profile, $key, $elementKey, Request $request, Configuration $config): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        if ($request->getMethod() === 'POST') {
            $value = $config->get($key, 'list-option', $profile, []);

            $pointer = 0;

            foreach ($value as $listKey => $listValue) {
                if ($pointer === (int)$elementKey) {
                    $elementKey = $listKey;
                    break;
                }

                ++$pointer;
            }

            unset($value[$elementKey]);
            $config->set($key, $value, 'list-option', $profile);

            return $this->redirectToRoute('metador_admin_lists_show', ['profile' => $profile, 'key' => $key]);
        }

        return $this->render('basicProfile/Lists/confirmElement.html.twig', [
            'listProfile'    => $profile,
            'listKey'        => $key,
            'listElementKey' => $elementKey
        ]);
    }

    #[Route('/edit/element/{profile}/{key}/{elementKey}', name: 'metador_admin_lists_edit_element', defaults: ['elementKey' => ''], methods: ['GET', 'POST'])]
    public function editElementAction($profile, $key, $elementKey, Configuration $config): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        $value = $config->get($key, 'list-option', $profile, []);

        $pointer = 0;

        foreach ($value as $listKey => $listValue) {
            if ($pointer === (int)$elementKey) {
                $elementKey = $listKey;
                break;
            }

            ++$pointer;
        }

        return $this->render('basicProfile/Lists/editElement.html.twig', [
            'listProfile'    => $profile,
            'listKey'        => $key,
            'key'            => $elementKey,
            'value'          => $value[$elementKey]
        ]);
    }

    #[Route('/confirm/{profile}/{key}', name: 'metador_admin_lists_confirm', methods: ['GET', 'POST'])]
    public function confirmAction($profile, $key, Request $request, Configuration $config): RedirectResponse|Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_GEO_OFFICE');

        if ($request->getMethod() === 'POST') {
            $config->remove($key, 'list-option', $profile);

            return $this->redirectToRoute('metador_admin_lists');
        }

        return $this->render('basicProfile/Lists/confirm.html.twig', [
            'listProfile'    => $profile,
            'listKey'        => $key
        ]);
    }
}
