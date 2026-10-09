<?php

namespace App\Service\Map;

use App\Component\Map\MapInterface;
use App\Component\Map\WmsCapabilitiesParser;
use App\Repository\WmsRepository;
use App\Service\Browser;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Wms as WmsEntity;

class Map implements MapInterface
{
    protected $repo = null;
    protected ?Browser $browser = null;

    const ENTITY = "MetadorMapBundle:Wms";

    public function __construct(EntityManagerInterface $em, Browser $browser, WmsRepository $wmsRepository)
    {
        $this->repo = $wmsRepository;
        $this->browser = $browser;
    }

    public function __destruct()
    {
        unset(
            $this->repo
        );
    }

    public function get($id)
    {
        return $this->repo->findOneById($id);
    }

    public function all()
    {
        return $this->repo->findBy([], ['priority' => 'DESC']);
    }

    public function save($entity): static
    {
        $this->repo->save($entity);

        return $this;
    }

    public function remove($entity): static
    {
        $this->repo->remove($entity);

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function update($url, WmsEntity &$wms): WmsEntity
    {
        $url_ = Map::getValidGcUrl($url);
        $doc = WmsCapabilitiesParser::createDocument($this->getContent($url_));
        $parser = WmsCapabilitiesParser::getParser($doc);

        return $parser->parse($wms);
    }

    private function getContent($url): string
    {
        $response = $this->browser->get($url);

        if ($response->content === false) {
            throw new \Exception('Die URL liefert keine Daten.');
        }

        return $response->content;
    }

    private static function getValidGcUrl($url): ?string
    {
        if ((bool)($urlArray = parse_url(trim($url)))) {
            $service = null;
            $request = null;
            $version = null;
            $getParams = [];
            if (isset($urlArray['query'])) {
                parse_str($urlArray['query'], $getParams);
                foreach ($getParams as $key => $value) {
                    if (strtoupper($key) === 'SERVICE') {
                        $service = strtoupper($value);
                    } elseif (strtoupper($key) === 'REQUEST') {
                        $request = strtoupper($value);
                    } elseif (strtoupper($key) === 'VERSION') {
                        $version = strtoupper($value);
                    }
                }
            }

            if (!$service) {
                $getParams['SERVICE'] = 'WMS';
            }
            if (!$request) {
                $getParams['REQUEST'] = 'GetCapabilities';
            }
            if (!$version) {
                $getParams['VERSION'] = '1.3.0';
            }
            $pos = strpos($url, '?');
            $query = http_build_query($getParams);
            if ($pos === false) {
                $urlNew = $url.'?'.$query;
            } else {
                $urlNew = substr($url, 0, $pos + 1).$query;
            }

            return $urlNew;
        } else {
            return null;
        }
    }

    public function toOpenLayers(WmsEntity $wms): array
    {
        return [
            'type' => $wms::$type,
            'url' => $wms->getGmUrl(),
            'title' => $wms->getTitle(),
            'visible' => $wms->isVisible(),
            'opacity' => $wms->getOpacity(),
            'params' => [
                'LAYERS' => implode(',', $wms->getLayers()),
                'VERSION' => $wms->getVersion(),
                'FORMAT' => $wms->getFormat(),
            ],
        ];
    }
}
