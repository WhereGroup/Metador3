<?php

namespace App\Service\Import;

use App\Component\XmlParser;
use App\Component\XmlParserFunctions;
use App\Service\Plugin;
use Symfony\Component\HttpKernel\KernelInterface;

class MetadataImport implements MetadataImportInterface
{
    private KernelInterface $kernel;
    private Plugin $plugin;

    public function __construct(KernelInterface $kernel, Plugin $plugin)
    {
        $this->kernel = $kernel;
        $this->plugin = $plugin;
    }

    public function __destruct()
    {
        unset(
            $this->kernel,
            $this->plugin
        );
    }

    public function load($xml, $pluginClassName)
    {
        $parser = new XmlParser($xml, new XmlParserFunctions());

        $array  = $parser
            ->loadSchema(file_get_contents($this->getSchemaFile($pluginClassName)))
            ->parse();


        return $array['p'] ?? [];
    }

    // Todo: change to $pluginName
    protected function getSchemaFile($pluginClassName)
    {
//        $path = $this->plugin->getPath('metador_import', '/json/import.json');
        return $this->kernel->locateResource('@' . $pluginClassName . '/Resources/config/import.json');
    }

    protected function getHierarchyLevel($string)
    {
        $dom = new \DOMDocument();
        $dom->loadXml($string);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
        $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');
        $xpath->registerNamespace('srv', 'http://www.isotc211.org/2005/srv');
        $xpath->registerNamespace('gml', 'http://www.opengis.net/gml');
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $hierarchyLevel = null;

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/text()");
        if ($result->length === 1) {
            $hierarchyLevel = $result->item(0)->nodeValue;
        }

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue");
        if ($result->length === 1) {
            $hierarchyLevel = $result->item(0)->value;
        }

        if ($hierarchyLevel === 'series') {
            return 'dataset';
        }

        return $hierarchyLevel;
    }

    /**
     * @throws \Exception
     */
    public function loadWMS($xml)
    {
        $path = $this->plugin->getPath('metador_import', '/json/');

        // read version
        $parser = new XmlParser($xml, new XmlParserFunctions());
        $parser->loadSchema(file_get_contents($path . 'wmsversion.json'));
        $version = $parser->parse();

        unset($parser);

        // read metadata
        $parser = new XmlParser($xml, new XmlParserFunctions());

        switch ($version["version"]) {
            case "1.1.1":
                $parser->loadSchema(file_get_contents($path . 'wms_1-1-1.json'));
                break;
            case "1.3.0":
                $parser->loadSchema(file_get_contents($path . 'wms_1-3-0.json'));
                break;
        }

        $array = $parser->parse();

        return $array['p'] ?? [];
    }

    /**
     * @throws \Exception
     */
    public function loadWFS($xml)
    {
        $path = $this->plugin->getPath('metador_import', '/json/');

        // read version
        $parser = new XmlParser($xml, new XmlParserFunctions());
        $parser->loadSchema(file_get_contents($path . 'wfsversion.json'));
        $version = $parser->parse();

        unset($parser);

        // read metadata
        $parser = new XmlParser($xml, new XmlParserFunctions());

        switch ($version["version"]) {
            case "1.1.0":
            case "1.1.3":
                $parser->loadSchema(file_get_contents($path . 'wfs_1-1-0.json'));
                break;
            case "2.0.0":
            case "2.0.1":
            case "2.0.2":
                $parser->loadSchema(file_get_contents($path . 'wfs_2-0-0.json'));
                break;
        }

        $array = $parser->parse();
        $p = $array['p'] ?? [];
        if (isset($p["bbox"])) {
            $bbox = null;
            foreach ($p["bbox"] as $ftbbox) {
                if (isset($ftbbox["lowerCorner"]) && isset($ftbbox["upperCorner"])) {
                    $lc = explode(" ", $ftbbox["lowerCorner"]);
                    $uc = explode(" ", $ftbbox["upperCorner"]);
                    $w = floatval($lc[0]);
                    $s = floatval($lc[1]);
                    $e = floatval($uc[0]);
                    $n = floatval($uc[1]);
                    if ($bbox === null) {
                        $bbox = [
                            "nLatitude" => $n,
                            "eLongitude" => $e,
                            "sLatitude" => $s,
                            "wLongitude" => $w,
                        ];
                    } else {
                        $bbox["nLatitude"] = max($bbox["nLatitude"], $n);
                        $bbox["eLongitude"] = max($bbox["eLongitude"], $e);
                        $bbox["sLatitude"] = min($bbox["sLatitude"], $s);
                        $bbox["wLongitude"] = min($bbox["wLongitude"], $w);
                    }
                }
            }
            if ($bbox) {
                $p["bbox"] = $bbox;
            }
        }

        return $p;
    }
}
