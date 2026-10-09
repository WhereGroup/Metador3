<?php

namespace App\Component\Map;

use App\Entity\Wms;

abstract class WmsCapabilitiesParser
{
    protected \DOMDocument $doc;
    protected \DOMXPath $xpath;
    protected int $resolution = 72;

    public function __construct(\DOMDocument $doc)
    {
        $this->doc = $doc;
        $this->xpath = new \DOMXPath($doc);
        $this->xpath->registerNamespace("xlink", "http://www.w3.org/1999/xlink");
    }

    protected function setResolution(int $resolution): void
    {
        $this->resolution = $resolution;
    }

    protected function getValue(string $xpath, \DOMNode $contextElm = null)
    {
        if (!$contextElm) {
            $contextElm = $this->doc;
        }
        try {
            $elm = $this->xpath->query($xpath, $contextElm)->item(0);
            if (!$elm) {
                return null;
            }
            if ($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } elseif ($elm->nodeType == XML_TEXT_NODE) {
                return $elm->wholeText;
            } elseif ($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm;
            } elseif ($elm->nodeType == XML_CDATA_SECTION_NODE) {
                return $elm->wholeText;
            } else {
                return null;
            }
        } catch (\Exception $E) {
            return null;
        }
    }

    abstract public function parse(Wms &$wms): Wms;

    public static function createDocument($data): \DOMDocument
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($data)) {
            throw new \Exception("Die URL liefert kein XML Dokument.");
        }

        // substitute xincludes
        $doc->xinclude();
        if ($doc->documentElement->tagName == "ServiceExceptionReport") {
            $message = $doc->documentElement->nodeValue;
            throw new \Exception($message);
        }

        if ($doc->documentElement->tagName !== "WMS_Capabilities"
            && $doc->documentElement->tagName !== "WMT_MS_Capabilities") {
            throw new \Exception("Die URL liefert kein WMS GetCapabilities Dokument.");
        }

        $version = $doc->documentElement->getAttribute("version");
        if ($version !== "1.1.1" && $version !== "1.3.0") {
            throw new \Exception('Die WMS GetCapabilites Version "'.$version.'" ist nicht unterstützt.');
        }

        return $doc;
    }

    public static function getParser(\DOMDocument $doc): WmsCapabilitiesParser130|WmsCapabilitiesParser111
    {
        $version = $doc->documentElement->getAttribute("version");
        return match ($version) {
            "1.1.1" => new WmsCapabilitiesParser111($doc),
            "1.3.0" => new WmsCapabilitiesParser130($doc),
            default => throw new \Exception('Die WMS GetCapabilites Version "' . $version . '"  ist nicht unterstützt'),
        };
    }

    protected function selectFormat(array $formats, array $pattern)
    {
        foreach ($pattern as $pat) {
            foreach ($formats as $format) {
                if (str_starts_with($format, $pat)) {
                    return $format;
                }
            }
        }

        return count($formats) > 0 ? $formats[0] : '';
    }
}
