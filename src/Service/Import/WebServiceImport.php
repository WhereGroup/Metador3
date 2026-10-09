<?php
namespace App\Service\Import;

use App\Service\Browser;
use Exception;
use Ramsey\Uuid\Uuid;

class WebServiceImport
{
    private MetadataImportInterface $metadataImport;
    private Browser $browser;

    public function __construct(MetadataImportInterface $metadataImport, Browser $browser) {
        $this->metadataImport = $metadataImport;
        $this->browser        = $browser;
    }

    public function parseGetCapabilitiesUrls($string)
    {
        preg_match_all("/(htt[^\n]+)/i", $string, $matches);

        return (empty($matches[1]))
            ? false
            : $matches[1];
    }

    public function isGetCapabilitiesUrl($url): false|array
    {
        if ((bool)($urlArray = parse_url(trim($url)))) {
            try {
                parse_str($urlArray['query'], $getParams);
                $service = null;

                foreach ($getParams as $key => $value) {
                    if (strtoupper($key) === 'SERVICE') {
                        $service = strtoupper($value);
                        break;
                    }
                }

                $p = [];
                $response = $this->browser->get($url);

                if ($service === 'WMS') {
                    $p = $this->metadataImport->loadWMS($response->content);
                } elseif ($service === 'WFS') {
                    $p = $this->metadataImport->loadWFS($response->content);
                }

                if (!empty($p)) {
                    return $p;
                }
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }

    public function convertUrlToUuid($url): false|string
    {
        try {
            $urlArray = parse_url(strtolower(trim($url)));

            $urlArray['query'] = isset($urlArray['query'])
                ? explode("&", $urlArray['query'])
                : [];

            sort($urlArray['query']);

            $string =
                $urlArray['host'] .
                $urlArray['path'] .
                implode("", $urlArray['query']);

            $string = str_replace("/", "", $string);

            return Uuid::uuid5(Uuid::NAMESPACE_DNS, $string)->toString();
        } catch (Exception $e) {
            return false;
        }
    }
}
