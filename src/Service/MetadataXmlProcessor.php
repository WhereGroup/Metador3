<?php

namespace App\Service;

use App\Component\XmlParser;
use App\Component\XmlParserFunctions;
use DOMDocument;
use DOMXPath;
use Exception;
use RuntimeException;
use App\Component\Exceptions\MetadataException;
use Twig\Environment;


class MetadataXmlProcessor
{
    public function __construct(
        private readonly Plugin $plugin,
        private readonly Environment $templating,
        //private readonly ParameterBagInterface $parameterBag
    )
    {
    }

    public function objectToXml(array $p, string $xmlPath): string
    {
        if (!isset($p['_profile'])) {
            throw new MetadataException('Profil in Datensatz ' . $p['_uuid'] . ' nicht angegeben!');
        }

        try {
            return $this->templating->render($xmlPath, ["p" => $p]);
        } catch (Exception $e) {
            throw new MetadataException('Datensatz ' . $p['_uuid'] . ' konnte nicht in XML umgewandelt werden.');
        }
    }

    public function xmlToObjectByProfile($xml, $profile): array
    {
        $parser = new XmlParser($xml, new XmlParserFunctions());
        $schema = $this->findSchemaByProfileName($profile);
        $json = file_get_contents($schema);

        $result = $parser->loadSchema($json)->parse();

        $result['p']['_profile'] = $profile;

        if (!empty($result['p']['fileIdentifier'])) {
            $result['p']['_uuid'] = $result['p']['fileIdentifier'];
        }

        if (empty($result['p']['_uuid'])) {
            throw new RuntimeException("FileIdentifier not found.");
        }

        if (empty($result['p']['hierarchyLevel'])) {
            throw new RuntimeException("HierarchyLevel not found.");
        }

        if (!empty($result["p"]["identificationId"])) {
            $result["p"]["identificationId"] = preg_replace('/([^"_]+_)+/', "", $result["p"]["identificationId"]);
        }

        return $result['p'];
    }

    public function xmlToObjectByMapping($xml, $mapping): array
    {
        $hierarchyLevel = $this->findHierarchyLevelFromXml($xml);

        if (is_null($hierarchyLevel)) {
            throw new RuntimeException("HierarchyLevel not found.");
        } elseif (!isset($mapping[$hierarchyLevel])) {
            throw new RuntimeException("HierarchyLevel not found in Mapping.");
        }

        return $this->xmlToObjectByProfile($xml, $mapping[$hierarchyLevel]);
    }

    /**
     * @param $xml
     * @return string|null
     */
    public function findHierarchyLevelFromXml($xml): ?string
    {
        $dom = new DOMDocument();
        $dom->loadXml($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('gmd', 'http://www.isotc211.org/2005/gmd');
        $xpath->registerNamespace('gco', 'http://www.isotc211.org/2005/gco');
        $xpath->registerNamespace('srv', 'http://www.isotc211.org/2005/srv');
        $xpath->registerNamespace('gml', 'http://www.opengis.net/gml');
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/text()");
        if ($result->length === 1) {
            return $result->item(0)->nodeValue;
        }

        $result = $xpath->query("/*/gmd:hierarchyLevel/gmd:MD_ScopeCode/@codeListValue");
        if ($result->length === 1) {
            return $result->item(0)->value;
        }

        return null;
    }

    protected function findSchemaByProfileName(string $profile): ?string
    {
        try {
            return $this->plugin->getPath($profile, '/Resources/import.json');
        } catch (Exception $e) {
            return null;
        }
    }
}
