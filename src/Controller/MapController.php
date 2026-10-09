<?php

namespace App\Controller;

use App\Component\AjaxResponse;
use App\Component\Map\XmlUtils\EXmlReader;
use App\Component\Map\XmlUtils\FeatureJsonWriter;
use App\Component\Map\XmlUtils\XmlAssocArrayReader;
use App\Entity\Wms;
use App\Kernel;
use App\Service\FrontendCommand;
use App\Service\Logger;
use App\Service\Map\Map;
use App\Service\User;
use ShapeFile\ShapeFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MapController extends AbstractController
{

    public function __construct(private readonly Logger $logger, private readonly User $userService)
    {
    }

    public static $shapeSupportedTypes = [
        1 => 'Point',
        3 => 'LineString',//'PolyLine',
        5 => 'Polygon',
    ];

    #[Route('/loadwms/', name: 'metador_map_loadwms', methods: ['GET'])]
    public function loadWmsAction(Request $request, Map $map, FrontendCommand $frontendCommand)
    {
        $url = urldecode($request->query->get('url'));
        try {
            $wms = new Wms();
            $map->update($url, $wms);

            return new AjaxResponse($map->toOpenLayers($wms));
        } catch (\Exception $e) {
            $this->log('error', 'create', $e->getMessage(), false, false);

            $result = [];

            $frontendCommand->displayError($result, $e->getMessage());

            return new AjaxResponse($result);
        }
    }

    #[Route('/uploadgeom/', name: 'metador_map_uploadgeom', methods: ['POST'])]
    public function uploadGeomAction(Kernel $kernel, Request $request, FrontendCommand $frontendCommand)
    {
        $result = [
            'content' => null,
        ];
        if ($request->files->count() === 0) {
            $frontendCommand->displayError(
                $result,
                'Keine Datei wurde hochgeladen bzw. die Datei ist zu groß.'
            );
        }
        /** @var UploadedFile $file */
        foreach ($request->files as $file) {
            if (!$file instanceof UploadedFile) {
                return new Response('Die Hochgeladene Datei kann nicht gefunden werden.');
            }
            if ($file->getClientOriginalExtension() === 'xml' || $file->getClientOriginalExtension() === 'gml') {
                $reader = EXmlReader::create($file->getRealPath());
                $map = ['wfs:member', 'gml:featureMember'];
                $reader->addReader($map, new XmlAssocArrayReader($reader, new FeatureJsonWriter()));
                $result['content'] = [
                    "type" => "FeatureCollection",
                    "features" => [],
                ];
                try {
                    while ($reader->readComponent()) {
                        $result['content']['features'][] = $reader->getContent();
                        if (!isset($result['content']['crs']) && isset($result['content']['features'][0]['crs'])) {
                            $result['content']['crs'] = $result['content']['features'][0]['crs'];
                        }
                        break; // only first geometry
                    }
                } catch (\Exception $e) {
                    $result['content'] = null;
                    $frontendCommand->displayError($result, $e->getMessage());
                } finally {
                    $reader->close();
                }
            } elseif ($file->getClientOriginalExtension() === 'zip') {
                $zip = new \ZipArchive;
                if ($zip->open($file->getRealPath()) !== true) {
                    return new Response('Die Datei kann nicht entzippt werden.');
                }
                $tempPath = $this->getParameter('metador_temp_dir');

                $tempFolder = $tempPath.$file->getFilename();
                $zip->extractTo($tempFolder);
                $zip->close();
                try {
                    $onlyName = substr($file->getClientOriginalName(), 0, -4);
                    $files = $this->findFiles($tempFolder, $onlyName.'.shp');
                    if (count($files) === 0) {
                        throw new \Exception('Die Shape Datei "'.$onlyName.'.shp'.'" ist nicht vorhanden.', 0);
                    }
                    $basicName = substr($tempFolder.'/'.$files[0], 0, -4);
                    $shapes = [
                        'shp' => $this->getFileName($basicName, '.shp'),
                        'shx' => $this->getFileName($basicName, '.shx'),
                        'dbf' => $this->getFileName($basicName, '.dbf'),
                        'prj' => $this->getFileName($basicName, '.prj'),
                    ];
                    // Read all shapefiles
                    $shapeFile = new ShapeFile($shapes, ShapeFile::FLAG_SUPPRESS_Z + ShapeFile::FLAG_SUPPRESS_M);
                    $prj = $shapeFile->getPRJ();
                    $epsg = $this->findCrs($prj);
                    if ($epsg === null) {
                        throw new \Exception('Das Koordinatenreferenzsystem kann nicht ermittelt werden'
                            .' bzw. ist nicht unterstützt.');
                    }
                    $shtype = $shapeFile->getShapeType();
                    if (!isset(self::$shapeSupportedTypes[$shtype])) {
                        throw new \Exception('Der Geometrietyp ist nicht unterstützt:'.$shtype);
                    }
                    $type = self::$shapeSupportedTypes[$shtype];
                    $result['content'] = [
                        "type" => "FeatureCollection",
                        "crs" => [
                            "type" => "name",
                            "properties" => [
                                "name" => $epsg,
                            ],
                        ],
                        "features" => [],
                    ];

                    foreach ($shapeFile as $i => $record) {
                        if ($record['dbf']['_deleted']) {
                            continue;
                        }
                        $coords = $this->shGeomToJson($type, $record['shp']['parts'][0]);
                        if ($coords === null) {
                            throw new \Exception('Die Geometrie kann nicht ausgelesen werden.');
                        }
                        $result['content']['features'][] = [
                            "type" => "Feature",
                            "geometry" => [
                                "type" => $type,
                                "coordinates" => $coords,
                            ],
                            "properties" => [],
                        ];
                        break; // only first geometry
                    }
                } catch (\Exception $e) {
                    switch ($e->getCode()) {
                        case 0:
                            $frontendCommand->displayError($result, $e->getMessage());
                            break;
                        default:
                            $frontendCommand->displayError(
                                $result,
                                "Die Shape Datei kann nicht ausgelesen werden: ".$e->getMessage()
                            );
                    }
                } finally {
                    // remove all uploaded files
                    $fs = new Filesystem();
                    $fs->remove($file->getRealPath());
                    $this->delTree($tempFolder);
                }
            }
            break; // only first file
        }

        return new AjaxResponse($result);
    }

    private function getFileName($basicPath, $extension)
    {
        $file = $basicPath.$extension;
        if (file_exists($file)) {
            return $file;
        } else {
            throw new \Exception('Die Datei "'.$file.'" ist nicht vorhanden', 0);
        }
    }

    private function findFiles($dirPath, $fileRegex)
    {
        $matchedfiles = [];
        $all = opendir($dirPath);
        while ($file = readdir($all)) {
            if (is_file($dirPath.'/'.$file) && strpos(strtolower($file), strtolower($fileRegex)) !== false) {
                $matchedfiles[] = $file;
            }
        }
        closedir($all);

        return $matchedfiles;
    }

    private function log($type, $operation, $message, $addUser = true, $flash = true)
    {
        $log = $this->logger->newLog();

        $log
            ->setFlashMessage($flash)
            ->setType($type)
            ->setCategory('application')
            ->setSubcategory('map')
            ->setOperation($operation)
            ->setMessage($message);
        if ($addUser) {
            $log->setUsername($this->userService->getUsernameFromSession());
        }

        $this->logger->set($log);
        unset($log);
    }

    private function findCrs($projDef)
    {
        $help = $this->prepareStr($projDef);
        $map = $this->getParameter('map_shape_epsg');
        if ($map === null) {
            throw new \Exception('Kein Koordinatreferenzsystem ist vorhanden.');
        }
        foreach ($map as $key => $epsg) {
            if (strpos($help, $this->prepareStr($key)) !== false) {
                return $epsg;
            }
        }

        return null;
    }

    private function prepareStr($str)
    {
        return preg_replace('/[^0-9a-z]/', '', $str);
    }

    private function delTree($dir)
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    private function shGeomToJson($type, $part)
    {
        switch ($type) {
            case 'LineString':
                $res = [];
                foreach ($part['points'] as $point) {
                    $res[] = [$point['x'], $point['y']];
                }

                return $res;
            case 'Polygon':
                $rings = [];
                foreach ($part['rings'] as $item) {
                    $ring = [];
                    foreach ($item['points'] as $point) {
                        $ring[] = [$point['x'], $point['y']];
                    }
                    $rings[] = $ring;
                }

                return $rings;
            default:
                return null;
        }
    }
}
