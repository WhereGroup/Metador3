<?php


namespace App\Component\CswHarvester;

use App\Service\Browser;

class GetRecordsClient
{

    const QUERY_PARAMS = [
        "request" => "GetRecords",
        "service" => "CSW",
        "version" => "2.0.2",
        "outputSchema" => "http://www.isotc211.org/2005/gmd",
        "elementSetName" => "full",
        "constraintLanguage" => "FILTER",
        "typeNames" => "gmd:MD_Metadata",
    ];

    public static function getHitsUrl($url, array $filter = []): string
    {
        $params = array_merge(self::QUERY_PARAMS, count($filter) === 0 ? [] : $filter);
        $params["resultType"] = "hits";

        return GetRecordsClient::generateUrl($url, $params);
    }

    public static function getResultssUrl($url, array $filter = [], $maxRecords = 20, $startPosition = 1): string
    {
        $params = array_merge(self::QUERY_PARAMS, count($filter) === 0 ? [] : $filter);
        $params["resultType"] = "results";
        $params["startPosition"] = $startPosition;
        $params["maxRecords"] = $maxRecords;

        return GetRecordsClient::generateUrl($url, $params);
    }

    public static function getRecords(Browser $browser, string $url, array $filter = [], $maxRecords = 20, $startPosition = 1): array
    {
        $fullUrl = GetRecordsClient::getResultssUrl($url, $filter, $maxRecords, $startPosition);
        $result = $browser->get($fullUrl);

        try {
            $xml = Xml::create($result->content);
            $val = $xml->getValue("/csw:GetRecordsResponse/csw:SearchResults/gmd:MD_Metadata", null, false);
            $res = [];
            /**
             * @var \DOMElement $el
             */
            foreach ($val as $el) {
                $res[] = $el->ownerDocument->saveXML($el);
            }
            return $res;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(). " URL:".$url);
        }
    }

    public static function getHits(Browser $browser, $url, array $filter = []): int
    {
        $fullUrl = GetRecordsClient::getHitsUrl($url, $filter);
        $result = $browser->get($fullUrl);
        try {
            $xml = Xml::create($result->content);
            $val = $xml->getValue("/csw:GetRecordsResponse/csw:SearchResults/@numberOfRecordsMatched");
            return $val === null || is_array($val) ? -1 : intval($val);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(). " URL:".$url);
        }

    }

    public static function generateUrl($url, array $params = []): string
    {
        $uri = parse_url($url);
        $uri["params"] = array_merge($uri["params"] ?? [], $params);
        return (empty($uri["scheme"]) ? "http://" : $uri["scheme"] . "://") . $uri["host"]
            . (empty($uri["port"]) ? "" : ":" . $uri["port"]) . (empty($uri["path"]) ? "" : $uri["path"])
            . (count($uri["params"]) > 0 ? "?" . http_build_query($uri["params"]) : '')
            . (empty($uri["fragment"]) ? "" : "#" . $uri["fragment"]);
    }
}
