<?php

namespace App\Controller\CatalogueService;

use App\Component\CatalogueService\CswException;
use App\Component\CatalogueService\Exception\GetCapabilitiesNotFoundException;
use App\Component\CatalogueService\Parameter\Parameter;
use App\Service\Search\PropertyNameNotFoundException;
use Exception;
use App\Entity\Csw;
use App\Service\CatalogueService\Csw as CswService;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

#[Route('/csw/')]
class CswController extends AbstractController
{
    public function __construct(private readonly Environment $templating)
    {}

    #[Route('{source}/{slug}', name: 'csw_default', methods: ['GET', 'POST'])]
    public function defaultAction(Request $request, $source, $slug, CswService $cswService, RouterInterface $router): Response
    {
//        $this->logRequest($request);

        $csw = $cswService;

        /** @var Csw $cswConfig */
        $cswConfig = $csw->findOneBySlugAndSource($slug, $source);

        if ($cswConfig === null) {
            throw new NotFoundHttpException('Csw mit slug:"'.$slug.'" und source:"'.$source.'" existiert nicht.');
        }

        if ($cswConfig->getActive() !== true) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $content = null;

        try {
            /*  @var Parameter $parameter */
            $parameter = null;
            if ($request->getMethod() === 'GET') {
                $parameter = $csw->readGetParameter($request->query->all());
            } else {
                $parameter = $csw->readPostParameter($request->getContent());
            }

            switch ($parameter->getOperationName()) {
                case 'GetCapabilities':
                    $params = [
                        'source' => $cswConfig->getSource(),
                        'slug' => $cswConfig->getSlug(),
                    ];
                    $content = $csw->getCapabilities(
                        $parameter,
                        $cswConfig,
                        $router->generate('csw_default', $params, UrlGeneratorInterface::ABSOLUTE_URL),
                        $router->generate('csw_transaction', $params, UrlGeneratorInterface::ABSOLUTE_URL)
                    );
                    break;
                case 'DescribeRecord':
                    $content = $csw->describeRecord($parameter, $cswConfig);
                    break;
                case 'GetRecordById':
                    return $csw->getRecordById($parameter, $cswConfig);
                case 'GetRecords':
                    return $csw->getRecords($parameter, $cswConfig);
                default:
                    throw new CswException('request', CswException::OPERATIONNOTSUPPORTED);
            }
        } catch (Exception $ex) {
            return $this->renderException($ex);
        }

        return new Response($content, Response::HTTP_OK, ['content-type' => 'application/xml; charset=utf-8']);
    }

    #[Route('manager/{source}/{slug}', name: 'csw_transaction', methods: ['POST'])]
    public function transactionAction(Request $request, $source, $slug, \Symfony\Component\Routing\RouterInterface $router, CswService $cswService): Response
    {
//        $this->logRequest($request);

        $csw = $cswService;
        $cswConfig = $csw->findOneBySlugAndSource($slug, $source);

        if ($cswConfig === null) {
            throw new NotFoundHttpException('Csw mit slug:"'.$slug.'" und source:"'.$source.'" existiert nicht.');
        }

        if ($cswConfig->getActive() !== true) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $content = null;
        try {
            /** @var Parameter $parameter */
            $parameter = $csw->readTransactionParameter($request->getContent());
            $content = $csw->transaction($parameter, $cswConfig);
        } catch (GetCapabilitiesNotFoundException $ex) {
            $url = $router->generate("csw_default", ["source" => $source, "slug" => $slug]);
            $path = [
                "_controller" => "Plugins\WhereGroup\CatalogueServiceBundle\Controller\CswController::defaultAction",
                "source" => $source,
                "slug" => $slug,
                'url' => $url,
            ];
            $subRequest = new Request(
                $request->query->all(),
                $request->request->all(),
                $path,
                $request->cookies->all(),
                [],
                $request->server->all(),
                $request->getContent()
            );

            return $this->container->get('http_kernel')->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
        } catch (Exception $ex) {
            return $this->renderException($ex);
        }

        return new Response($content, Response::HTTP_OK, ['content-type' => 'application/xml; charset=utf-8']);
    }

    private function renderException(Exception $ex): Response
    {
        if ($ex instanceof CswException) {
            $content = $this->templating->render(
                "catalogueService/CSW/exception.xml.twig",
                [
                    'exception' => [
                        'code' => $ex->getCswCode(),
                        'locator' => $ex->getLocator(),
                        'text' => $ex->getText(),
                    ],
                ]
            );

            return new Response($content, $ex->getHttpStatusCode(), ['content-type' => 'application/xml']);
        }

        if ($ex instanceof PropertyNameNotFoundException) {
            return $this->renderException(
                new CswException($ex->getMessage(), CswException::DUPLICATESTOREDQUERYPARAMETERNAME)
            );
        }

        return $this->renderException(new CswException('', CswException::NOAPPLICABLECODE, $ex));
    }

    private function logRequest(Request $request): void
    {
        if (!$this->container->hasParameter('log_csw') || $this->container->getParameter('log_csw') !== true) {
            return;
        }

        $time = microtime(true);
        $path = $this->kernel->getProjectDir() . '/var/logs/'
            . date('Y-m-d_H-i-s_', $time)
            . substr(strstr($time, '.'), 1) . '_'
            . $request->getMethod() . '.log';

        $data =
            "\nMethod: " . $request->getMethod() .
            "\nURL: " . $request->getUri();

        if ($request->getMethod() !== 'GET') {
            $data .= "\nContent:\n" . $request->getContent();
        }

        file_put_contents($path, $data);
    }
}
