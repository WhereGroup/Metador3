<?php

namespace App\Controller;

use App\Component\AjaxResponse;
use App\Component\Exceptions\MetadataException;
use App\Component\Exceptions\MetadataNotFoundException;
use App\Component\Metadata\Validator;
use App\Component\Utils\ArrayParser;
use App\Service\Configuration;
use App\Service\FrontendCommand;
use App\Service\Logger;
use App\Service\Metadata;
use App\Service\Plugin;
use App\Service\Search\DatabaseSearch;
use App\Service\User;
use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMText;
use DOMXPath;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metadata')]
class ProfileController extends AbstractController
{
    private $data = null;

    public function __construct(
        private readonly User $userService,
        private readonly Metadata $metadataService,
        private readonly Plugin $pluginService,
        private readonly FrontendCommand $frontendCommand,
        private readonly Logger $loggerService,
    ){}

    #[Route('/{source}/{profile}/new', name: 'metadata_new', methods: ['GET', 'POST'])]
    public function newAction($source, $profile): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');

        $template = $this->pluginService->getPluginTemplatePath($profile) . '/Profile/form.html.twig';

        return $this->render($template, [
            'p' => [
                '_source'   => $source,
                '_profile'  => $profile,
                '_public'   => false,
                '_groups'   => [],
            ],
            'userGroups' => $this->userService->getRoles(),
            'profile'    => $profile
        ]);
    }

    #[Route('/{source}/{profile}/use/{uuid}', name: 'metadata_use', methods: ['GET'])]
    public function useAction($source, $profile, $uuid): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');

        $template = $this->pluginService->getPluginTemplatePath($profile) . '/Profile/form.html.twig';
        $entity = $this->metadataService->findById($uuid);

        if (!$entity) {
            throw new MetadataNotFoundException($uuid . " not found.");
        }

        $p = $entity->getObject();

        unset($entity);

        unset(
            $p['fileIdentifier'],
            $p['_insert_time'],
            $p['_insert_user'],
            $p['_lock_time'],
            $p['_lock_user'],
            $p['_locked'],
            $p['_remove_lock'],
            $p['_update_time'],
            $p['_update_user'],
            $p['_username'],
            $p['_uuid'],
            $p['dateStamp']
        );

        $p['_source']  = $source;
        $p['_profile'] = $profile;
        $p['_public']  = false;
        $p['_groups']  = [];

        return $this->render($template, [
            'p'          => $p,
            'userGroups' => $this->userService->getRoles(),
            'profile'    => $profile
        ]);
    }

    #[Route('/{profile}/edit/{id}', name: 'metadata_edit', methods: ['GET', 'POST'])]
    public function editAction($profile, $id, Metadata $metadataService, User $userService, Security $security): Response
    {
        $metadata = $metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $p = $metadata->getObject();
        $this->denyAccessUnlessGranted('view', $p);

        if ($security->isGranted('edit', $p)) {
            $this->metadataService->lock($metadata);
        }

        $template = $this->pluginService->getPluginTemplatePath($profile) . '/Profile/form.html.twig';

        return $this->render($template, [
            'p' => $metadata->getObject(),
            'userGroups' => $userService->getRoles(),
            'profile'    => $profile
        ]);
    }

    #[Route('/{source}/{profile}/save', name: 'metadata_save', methods: ['POST'])]
    public function saveAction($source, $profile, Request $request): AjaxResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER');

        $p = $request->request->all('p');

        $submitType = $request->request->get('submit');
        $response = [];
        $uuid     = null;

        $id = empty($p['_uuid']) ? null : $p['_uuid'];


        // If id exists, get the metadata to check permission and keep some settings.
        $metadata = $this->metadataService->findById($id);

        if ($metadata) {
            // On abort unlock metadata and redirect to home
            if ($submitType === 'abort') {
                $this->metadataService->unlock($metadata);
                $this
                    ->frontendCommand
                    ->redirect($response, $this->generateUrl('metador_home'));

                return new AjaxResponse($response);
            }

            $oldObject = $metadata->getObject();
            $this->denyAccessUnlessGranted('edit', $oldObject);

            // Keep groups if user is not owner.
            if (!$this->isGranted('edit_group', $oldObject)) {
                $p['_groups'] = $oldObject['_groups'] ?? [];
            }

            // Keep owner
            $p['_insert_user'] = $metadata->getinsertUser()->getUsername();
            $p['_insert_time'] = date('Y-m-d', $metadata->getInsertTime());

            if ($submitType === 'close') {
                $p['_remove_lock'] = true;
            }
        }

        $this->metadataService->db->beginTransaction();
        try {
            if ($metadata) {
                $metadata = $this->metadataService
                    ->updateByObject($p, [ 'source'  => $source, 'profile' => $profile ]);
            } else {
                $metadata = $this->metadataService
                    ->insertByObject($p, ['source' => $source, 'profile' => $profile]);
            }

            $this->frontendCommand->changeLocation(
                $response,
                $this->generateUrl('metadata_edit', [
                    'source'  => $metadata->getSource(),
                    'profile' => $metadata->getProfile(),
                    'id'      => $metadata->getId()
                ])
            );

            $this->metadataService->db->transactionCommit();
        } catch (Exception $e) {
            $this->metadataService->db->transactionRollBack();

            if ($e instanceof MetadataException) {
                $this->frontendCommand->displayError($response, $e->getMessage());
            }
        }

        $response = array_merge_recursive($response, [
            'metadata' => [
                'id'   => $id
            ]
        ]);

        // Add redirect command to response
        if ($submitType === 'close') {
            $this
                ->frontendCommand
                ->redirect($response, $this->generateUrl('metador_home'));
        }

        return new AjaxResponse($response);
    }

    #[Route('/{profile}/confirm/{id}', name: 'metadata_confirm', methods: ['GET'])]
    public function confirmAction($profile, $id, Request $request): Response
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $this->denyAccessUnlessGranted('view', $metadata->getObject());

        $this->init($profile, $request);

        return $this->render(
            $this->getTemplate('/Profile/confirm'),
            [
                'id'      => $id,
                'profile' => $profile,
                'form'    => $this
                    ->createFormBuilder($metadata)
                    ->add('delete', SubmitType::class, ['label' => 'ok'])
                    ->getForm()
                    ->createView(),
            ]
        );
    }

    #[Route('/delete/{id}', name: 'metadata_delete', methods: ['POST'])]
    public function deleteAction($id, Request $request): RedirectResponse
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $this->denyAccessUnlessGranted('view', $metadata->getObject());

        $form = $this->createFormBuilder($metadata)
            ->add('delete', SubmitType::class, ['label' => 'ok'])
            ->getForm()
            ->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->metadataService->delete($metadata);
        }

        return $this->redirectToRoute('metador_home');
    }

    #[Route('/profile/validate/{id}', name: 'metadata_validate', methods: ['GET', 'POST'])]
    public function validateAction($id, Validator $validator): Response
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $p = $metadata->getObject();

        $this->denyAccessUnlessGranted('view', $metadata->getObject());

        $message = "You can only validate public metadata.";
        $debug   = [];

        if ($p['_public']) {
            $validator->isValid($p, $debug);
            $message = "";
        }

        return $this->render('profile/validate.html.twig', [
            'id'      => $id,
            'debug'   => $debug,
            'message' => $message
        ]);
    }

    #[Route('/profile/error/{id}', name: 'metadata_error', methods: ['GET', 'POST'])]
    public function errorAction($id): Response
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $p = $metadata->getObject();
        $this->denyAccessUnlessGranted('view', $p);

        return $this->render('profile/errors.html.twig', [
            'p' => $p
        ]);
    }

    #[Route('/profile/xpath/{id}', name: 'metadata_xpath', methods: ['GET', 'POST'])]
    public function xpathAction($id, Request $request): Response|AjaxResponse
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }
        $p = $metadata->getObject();

        $this->denyAccessUnlessGranted('view', $p);

        if ($request->getMethod() == 'POST') {
            $query = $request->request->get('xpath', '/*');

            $xml = $this->metadataService->getProcessor()->objectToXml(
                $p,
                $this->pluginService->getPluginTemplatePath($p['_profile']) . '/Export/metadata.xml.twig'
            );
            $xml   = preg_replace('/(>)([\t\n ^<]+)(<)/s', '${1}${3}', $xml);

            $doc = new DOMDocument();
            $doc->loadXml($xml);
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;

            $xpath   = new DOMXPath($doc);
            $entries = @$xpath->query($query);
            $html    = '';

            if ($entries) {
                foreach ($entries as $entry) {
                    if ($entry instanceof DOMElement) {
                        $entry->ownerDocument->preserveWhiteSpace = false;
                        $entry->ownerDocument->formatOutput = true;

                        $html .= '<pre class="xml-code">' . htmlentities($entry->ownerDocument->saveXML($entry)) . '</pre>';
                    } elseif ($entry instanceof DOMText) {
                        $html .= '<pre class="xml-code">' . htmlentities($entry->wholeText) . '</pre>';
                    } elseif ($entry instanceof DOMAttr) {
                        $html .= '<pre class="xml-code">' . htmlentities($entry->nodeValue) . '</pre>';
                    } else {
                        $html .= get_class($entry) . ' is not supported.';
                    }
                }
            }

            return new AjaxResponse(['html' => $html]);
        }

        return $this->render('profile/xpath.html.twig', [
            'id'    => $id,
            'xpath' => $request->get('xpath', '/*')
        ]);
    }

    private function parseXpaths(array $array, array &$xpath, string $subkey = "", string $path = ""): void
    {
        foreach ($array as $key => $value) {
            if (in_array($key, ['_cmd', '_asArray'])) {
                continue;
            }

            if (is_array($value) && isset($value['_path']) && isset($value['_data'])) {
                $this->parseXpaths(
                    $value['_data'],
                    $xpath,
                    $subkey . '_' . $key,
                    $path . '/' . $value['_path']
                );
                continue;
            }

            if (is_string($value)) {
                $xpath[
                    substr($subkey . '_' . $key, 3)
                ] = '/' . ltrim($path . '/' . $value, '/');
                continue;
            }
        }
    }

    #[Route('/profile/test/{id}', name: 'metadata_test', methods: ['GET', 'POST'])]
    public function testAction($id, Plugin $pluginService): Response
    {
        $metadata = $this->metadataService->findById($id);

        if (!$metadata) {
            throw new MetadataNotFoundException($id . " not found.");
        }

        $object1  = $metadata->getObject();
        $object2  = $this->metadataService->getProcessor()->xmlToObjectByProfile(
            $this->metadataService->getProcessor()->objectToXml(
                $object1,
                $pluginService->getPluginTemplatePath($object1['_profile']) . '/Export/metadata.xml.twig'
            ),
            $object1['_profile']
        );

        $file = $pluginService->getPath($object1['_profile'], '/Resources/import.json');

        $schema = json_decode(file_get_contents($file), true);
        $xpath = [];

        $this->parseXpaths($schema, $xpath);

        $arr1 = [];
        $arr2 = [];

        ArrayParser::flatten($object1, $arr1, true);
        ArrayParser::flatten($object2, $arr2, true);

        dump($object1);
        dump($object2);

        ksort($arr1);
        ksort($arr2);

        $key1 = array_keys($arr1);
        $key2 = array_keys($arr2);

        $p1 = 0;
        $p2 = 0;

        $result = [];

        while (isset($key1[$p1]) || isset($key2[$p2])) {
            if (isset($key1[$p1]) && isset($key2[$p2])) {
                if ($key1[$p1] == $key2[$p2]) {
                    $this->prepareTestResult($result, $xpath, $key1[$p1], $arr1[$key1[$p1]], $key2[$p2], $arr2[$key2[$p2]]);
                    $p1++;
                    $p2++;
                    continue;
                }

                if (in_array($key1[$p1], $key2) && !in_array($key2[$p2], $key1)) {
                    $this->prepareTestResult($result, $xpath, null, null, $key2[$p2], $arr2[$key2[$p2]]);
                    $p2++;
                    continue;
                }

                if (in_array($key2[$p2], $key1) && !in_array($key1[$p1], $key2)) {
                    $this->prepareTestResult($result, $xpath, $key1[$p1], $arr1[$key1[$p1]], null, null);
                    $p1++;
                    continue;
                }

                if (!in_array($key2[$p2], $key1) && !in_array($key1[$p1], $key2)) {
                    $this->prepareTestResult($result, $xpath, $key1[$p1], $arr1[$key1[$p1]], null, null);
                    $p1++;
                    continue;
                }
            } elseif (isset($key1[$p1]) && !isset($key2[$p2])) {
                $this->prepareTestResult($result, $xpath, $key1[$p1], $arr1[$key1[$p1]], null, null);
                $p1++;
                continue;
            } elseif (!isset($key1[$p1]) && isset($key2[$p2])) {
                $this->prepareTestResult($result, $xpath, null, null, $key2[$p2], $arr2[$key2[$p2]]);
                $p2++;
                continue;
            }

            $p1++;
            $p2++;
        }

        return $this->render('profile/test.html.twig', [
            'result' => $result,
            'id'     => $id
        ]);
    }

    #[Route('/profile/help', name: 'metadata_help', methods: ['GET', 'POST'])]
    public function helpAction(Request $request, Configuration $configurationService): AjaxResponse
    {
        $data = [];

        if ($request->getMethod() === 'POST') {
            $configurationService->set(
                $request->request->get('key', ''),
                $request->request->get('val', ''),
                'profile',
                'helptext'
            );
        } elseif ($request->getMethod() === 'GET') {
            $data['html'] = $this->render('theme/template/helptext.html.twig', [
                'html' => $configurationService->get($request->get('key', ''), 'profile', 'helptext', '')
            ]);
        }

        return new AjaxResponse($data);
    }

    #[Route('/profile/operateson', name: 'operateson_info', methods: ['GET'])]
    public function operatesOnAction(Request $request, DatabaseSearch $search, Configuration $config): AjaxResponse
    {
        $hits = $config->get('popup_search_hits', 'plugin', 'metador_core', 5);

        $response = $search
            ->setPage($request->query->get('page'))
            ->setHits($hits)
            ->setSource($request->query->get('source'))
            ->setProfile(['metador_dataset_profile'])
            ->setTerms($request->query->get('terms'))
            ->find()
        ;

        return new AjaxResponse([
            'data' => [],
            'html' => $this->renderView('basicProfile/Profile/operates-on.html.twig', $response)
        ]);
    }

    private function prepareTestResult(&$result, $xpath, $k1, $v1, $k2, $v2): void
    {
        if (str_starts_with($k1, '_') || str_starts_with($k2, '_')) {
            return;
        }

        $status = 'error';

        if (!empty($v1) && !is_null($k1) && !is_null($k2) && $this->normalize($v1) === $this->normalize($v2)) {
            $status = 'success';
        } elseif (!is_null($k1) && !is_null($k2) && $this->normalize($v1) === $this->normalize($v2)) {
            $status = 'info';
        }

        $i1 = preg_replace('/_[0-9]+_/', '_', $k1);
        $i2 = preg_replace('/_[0-9]+_/', '_', $k2);

        $x1 = $xpath[$i1] ?? '';
        $x2 = $xpath[$i2] ?? '';

        $result[$status][] = [
            'k1' => $k1,
            'v1' => $v1,
            'x1' => $x1,
            'k2' => $k2,
            'v2' => $v2,
            'x2' => $x2,
        ];
    }

    private function normalize($string): mixed
    {
        return str_replace("\r\n", "\n", $string);
    }

    private function init($profile, Request $request): void
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_USER', null, 'Unable to access this page!');
        $this->data['request'] = $request;
//        $this->data['className'] = $this->pluginService->getPluginClassName($profile);
        $this->data['user'] = $this->userService->getUserFromSession();
        $this->data['template'] = $this->pluginService->getPluginTemplatePath($profile);
        $this->data['p'] = $request->query->get('p');
        $this->data['p']['dateStamp'] = date("Y-m-d");
    }

    private function getTemplate(string $name): string
    {
        return $this->data['template'] . $name . '.html.twig';
    }

    private function log($type, $operation, $id, $message, array $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType($type)
            ->setFlashMessage()
            ->setCategory('metadata')
            ->setOperation($operation)
            ->setIdentifier($id)
            ->setMessage($message, $parameter);

        $this->loggerService->set($log);

        unset($log);
    }
}
