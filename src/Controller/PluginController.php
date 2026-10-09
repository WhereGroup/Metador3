<?php

namespace App\Controller;

use App\Entity\Plugin;
use App\Service\Logger;
use \App\Service\Plugin as PluginService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;
use ZipArchive;

#[Route('/admin/plugin')]
class PluginController extends AbstractController
{
    public function __construct(
        private readonly PluginService $pluginService,
        private readonly KernelInterface $kernel,
        private readonly Environment $twig,
        private readonly Logger $loggerService,
    ) {
    }

    #[Route('/', name: 'metador_admin_plugin', methods: ['GET', 'POST'])]
    public function indexAction(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return $this->render('plugin/index.html.twig', [
            'plugins' => $this->pluginService->getPlugins('origin')
        ]);
    }

//    #[Route('/update', name: 'metador_admin_plugin_update', methods: ['POST'])]
//    public function updateAction(Request $request): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        $plugins = [];
//        $array   = $request->request->all();
//
//        foreach ($array['plugin'] as $key => $value) {
//            if ($value == 1) {
//                $plugins[] = $key;
//            }
//        }
//
//        return $this->render('plugin/update.html.twig', [
//            'plugins' => implode(',', $plugins)
//        ]);
//    }

//    #[Route('/import', name: 'metador_admin_plugin_import', methods: ['GET'])]
//    public function importAction(): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        return $this->render('plugin/import.html.twig', [
//            'form' => $this
//                ->createFormBuilder(new Plugin())
//                ->add('attachment', FileType::class, [
//                    'label' => 'Datei', 'label_attr' => ['class' => 'plugin-label plugin-row'],
//                    'attr' => ['class' => 'plugin-row']
//                ])
//                ->add('save', SubmitType::class, ['label' => 'Importieren', 'attr' => [
//                    'class' => 'plugin-row'
//                ]])
//                ->getForm()
//                ->createView()
//        ]);
//    }

//    #[Route('/upload', name: 'metador_admin_plugin_upload', methods: ['POST'])]
//    public function uploadAction(Request $request): RedirectResponse|Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        $kernelPath = $this->kernel->getProjectDir();
//
//        $pluginPath = $kernelPath . '/../src/Plugins/';
//        $tempPath   = $kernelPath . '/../var/temp/';
//
//        $form = $this->createFormBuilder(new Plugin())
//            ->add('attachment', FileType::class, [
//                'label' => 'Datei', 'label_attr' => ['class' => 'plugin-label plugin-row'],
//                'attr' => ['class' => 'plugin-row']
//            ])
//            ->add('save', SubmitType::class, ['label' => 'Importieren', 'attr' => ['class' => 'plugin-row']])
//            ->getForm();
//
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            /** @var UploadedFile $file */
//            $file = $form['attachment']->getData();
//
//            if (!$file instanceof UploadedFile) {
//                $this->log('error', 'upload', '', 'Datei-Upload ist fehlgeschlagen.');
//                return $this->redirectToRoute('metador_admin_plugin');
//            }
//
//            // get extension
//            $extension = $file->guessExtension();
//
//            if (!$extension || $extension !== 'zip') {
//                $this->log('error', 'upload', '', 'Hochgeladene Datei ist kein Metador Plugin.');
//                return $this->redirectToRoute('metador_admin_plugin');
//            }
//
//            $pluginFile = $file->move($tempPath, $file->getFilename());
//
//            unset($file);
//
//            // extract to temp folder
//            $zip = new ZipArchive;
//
//            if ($zip->open($pluginFile->getRealPath()) !== true) {
//                $this->log('error', 'upload', '', 'Entpacken fehlgeschlagen.');
//                return $this->redirectToRoute('metador_admin_plugin');
//            }
//
//            $tempFolder = $tempPath . $pluginFile->getFilename() . '_dir';
//            $zip->extractTo($tempFolder);
//            $zip->close();
//
//            $fs = new Filesystem();
//
//            // remove uploaded file
//            $fs->remove($pluginFile->getRealPath());
//
//            // find bundles
//            $finder = new Finder();
//            $finder->in($tempFolder)->directories()->name('*Bundle');
//
//            foreach ($finder as $folder) {
//                /** @var SplFileInfo $folder  */
//                $copyPath = $pluginPath . $folder->getRelativePath() . '/';
//
//                if (!$fs->exists($pluginPath)) {
//                    $fs->mkdir($copyPath, 0644, true);
//                }
//
//                if ($fs->exists($copyPath . '/' . $folder->getFilename())) {
//                    $this->log('error', 'upload', '', 'Plugin ist existiert bereits.');
//                } else {
//                    $fs->mirror($folder->getRealPath(), $copyPath . $folder->getFilename());
//                }
//            }
//
//            $fs->remove($tempFolder);
//
//            return $this->redirectToRoute('metador_admin_plugin');
//        }
//
//        return $this->render('plugin/import.html.twig', [
//            'form' => $form->createView()
//        ]);
//    }

//    #[Route('/confirm/{plugin}', name: 'metador_admin_plugin_confirm', methods: ['GET'])]
//    public function confirmAction($plugin): Response
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        return $this->render('plugin/confirm.html.twig', [
//            'pluginKey' => $plugin
//        ]);
//    }
//
//    #[Route('/delete/{plugin}', name: 'metador_admin_plugin_delete', methods: ['POST'])]
//    public function deleteAction($plugin): RedirectResponse
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        $this->pluginService->delete($plugin);
//
//        return $this->redirectToRoute('metador_admin_plugin');
//    }

    #[Route('/view/{plugin}', name: 'metador_admin_plugin_view', methods: ['GET', 'POST'])]
    public function viewAction($plugin)
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        // Show template if exists

        if ($this->twig->getLoader()->exists($plugin . ':Plugin:view.html.twig')) {
            return $this->forward($plugin . ':Plugin:view');
        }

        return $this->render('plugin/view.html.twig');
    }

    #[Route('/command/config', name: 'metador_admin_plugin_command_config', methods: ['POST'])]
    public function configAction(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');

        return new JsonResponse($this->pluginService->update($request->request->all()));
    }

//    #[Route('/command/assets', name: 'metador_admin_plugin_command_assets', methods: ['POST'])]
//    public function assetsAction(): JsonResponse
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        return new JsonResponse($this->pluginService->assetsInstall());
//    }
//
//    #[Route('/command/database', name: 'metador_admin_plugin_command_database', methods: ['POST'])]
//    public function databaseAction()
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        return new JsonResponse($this->pluginService->doctrineUpdate());
//    }
//
//    #[Route('/command/cache', name: 'metador_admin_plugin_command_cache', methods: ['POST'])]
//    public function cacheAction()
//    {
//        $this->denyAccessUnlessGranted('ROLE_SYSTEM_SUPERUSER');
//
//        return new JsonResponse($this->pluginService->clearCache());
//    }

    private function log($type, $operation, $id, $message, array $parameter = []): void
    {
        $log = $this->loggerService->newLog();

        $log->setType($type)
            ->setMessage($message, $parameter)
            ->setFlashMessage()
            ->setCategory('system')
            ->setOperation($operation)
            ->setIdentifier($id);

        $this->loggerService->set($log);

        unset($log);
    }
}
