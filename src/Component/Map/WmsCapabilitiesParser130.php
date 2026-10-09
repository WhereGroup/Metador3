<?php

namespace App\Component\Map;

use App\Entity\Wms;
use DOMElement;
use DOMNodeList;

class WmsCapabilitiesParser130 extends WmsCapabilitiesParser
{
    public function __construct(\DOMDocument $doc)
    {
        parent::__construct($doc);

        foreach ($this->xpath->query('namespace::*', $this->doc->documentElement) as $node) {
            $nsPrefix = $node->prefix;
            $nsUri = $node->nodeValue;
            if ($nsPrefix == '' && $nsUri == 'http://www.opengis.net/wms') {
                $nsPrefix = 'wms';
            }
            $this->xpath->registerNamespace($nsPrefix, $nsUri);
        }
    }

    public function parse(Wms &$wms): Wms
    {
        $root = $this->doc->documentElement;
        $wms->setVersion($this->getValue('./@version', $root));
        $this->parseService($wms, $this->getValue('./wms:Service', $root));
        $wms->setFormats([]);
        $this->parseCapabilityRequest($wms, $this->getValue('./wms:Capability/wms:Request', $root));
        $wms->setLayerList([]);
        $this->parseLayer($wms, $this->getValue('./wms:Capability/wms:Layer', $root));
        // set default values: all available layers, a first founded format
        $wms->setLayers($wms->getLayerList());
        $wms->setFormat(
            $this->selectFormat($wms->getFormats(), ['image/png', 'image/jpg', 'image/jpeg', 'image/gif'])
        );

        return $wms;
    }

    private function parseService(Wms &$wms, DOMElement $contextElm): void
    {
        $title = $this->getValue('./wms:Title/text()', $contextElm);
        $wms->setTitle($title ? $title : WMS::TITLE_DEFAULT);
    }

    private function parseCapabilityRequest(Wms &$wms, DOMElement $contextElm): void
    {
        $wms->setGcUrl(
            $this->getValue(
                './wms:GetCapabilities/wms:DCPType/wms:HTTP/wms:Get/wms:OnlineResource/@xlink:href',
                $contextElm
            )
        );
        $wms->setGmUrl(
            $this->getValue(
                './wms:GetMap/wms:DCPType/wms:HTTP/wms:Get/wms:OnlineResource/@xlink:href',
                $contextElm
            )
        );

        $formatList = $this->xpath->query('./wms:GetMap/wms:Format', $contextElm);
        if ($formatList !== null) {
            foreach ($formatList as $item) {
                $wms->addFormat($this->getValue('./text()', $item));
            }
        }
    }

    private function parseLayer(Wms &$wms, DOMElement $contextElm): void
    {
        $name = $this->getValue('./wms:Name/text()', $contextElm);
        /* @var DOMNodeList $tempList */
        $tempList = $this->xpath->query('./wms:Layer', $contextElm);
        // add only named layer and no root or group layer
        if ($name !== null && $tempList->length === 0) {
            $wms->addToLayerList($name);
        }
        foreach ($tempList as $item) {
            $this->parseLayer($wms, $item);
        }
    }
}
