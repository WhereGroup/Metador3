<?php

namespace App\Service\Search;

class PrepareSearch
{
    public static function setFilter(Search &$search, &$params, $user = null, $download = null): void
    {

//        if (!is_null($download)) {
//            $params = json_decode(mb_convert_encoding(base64_decode($download), 'UTF-8', 'ISO-8859-1'), true);
//
//            foreach ($params as $name => $value) {
//                if (preg_match('/([^\[]+)\[([^\]]+)\]/', $name, $matches)) {
//                    if (empty($matches[1]) || empty($matches[2])) {
//                        continue;
//                    }
//                    $params[$matches[1]][$matches[2]] = $value;
//                }
//            }
//        }

        $search->setParmeters($params);

        $filter = [];

        if (isset($params['sort'])) {
            $column = empty($params['sort']) ? 'title' : $params['sort'];
            if ($column === 'date' || $column === 'dateStamp') {
                $column .= ' desc';
            }
            $search->setSort($column);
        }

        // Set source filter
        if (!empty($params['source'])) {
            $filter['and'][] = ['eq' => ['source' => $params['source']]];
        }

        // Set public filter if user is not logged in.
        if (is_null($user)) {
            $filter['and'][] = ['eq' => ['public' => true]];

            // Filter for logged in user.
        } elseif ($user->getUsername() !== 'root') {
            $search->setGroups($user->getRoles());
            $orx = [
                ['eq' => ['public'         => true]],
                ['eq' => ['insertUsername' => $user->getUsername()]],
            ];

            if (!empty($user->getNonSystemRoles())) {
                $orx[] = ['in' => ['role' => $user->getNonSystemRoles()]];
            }

            $filter['and'][] = ['or' => $orx];
        }

        if (isset($params['hierarchyLevel'])
            && is_array($params['hierarchyLevel']) && !empty($params['hierarchyLevel'])) {
            $subfilter = [];

            foreach ($params['hierarchyLevel'] as $key => $value) {
                if ($value !== 'true') {
                    continue;
                }
                $subfilter['or'][] = ['eq' => ['hierarchyLevel' => $key]];
            }

            if (isset($subfilter['or'])) {
                $filter['and'][] = $subfilter;
            }

            unset($subfilter);
        }

        if (isset($params['topicCategory']) && is_array($params['topicCategory']) && !empty($params['topicCategory'])) {
            foreach ($params['topicCategory'] as $key => $value) {
                if (!empty($value)) {
                    continue;
                }
                $filter['and'][] = ['neq' => ['topicCategory' => $key]];
            }
        }

        // Set date filter
        if (isset($params['date']) && !empty($params['date']['from'])) {
            $filter['and'][] = ['gte' => ['date' => $params['date']['from']]];
        }

        if (isset($params['date']) && !empty($params['date']['to'])) {
            $filter['and'][] = ['lte' => ['date' => $params['date']['to']]];
        }

        $expression = JsonFilterReader::read($filter, $search->createExpression());

        $search
            ->setTerms($params['terms'] ?? '')
            ->setExpression($expression);

        // Set spatial filter separate from others
        if (isset($params['spatial']) && $params['spatial']) {
            $spatial = JsonFilterReader::read($params['spatial'], $search->createExpression());
            /* @var SolrSearch $search */
            $search->setSpatialExpression($spatial);
        }
    }

    public static function setPagination(Search &$search, $params, $hits = 10, $download = null): void
    {
        if (is_null($download)) {
            $search
                ->setPage($params['page'] ?? 1)
                ->setHits($hits);
        } else {
            $search
                ->setPage(1)
                ->setHits(5000);
        }
    }
}
