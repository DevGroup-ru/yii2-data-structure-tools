<?php

namespace DevGroup\DataStructure\search\eventHandlers\QueryModifiers;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\search\base\SearchEvent;
use DevGroup\DataStructure\search\response\ResultResponse;
use DevGroup\DataStructure\traits\PropertiesTrait;

class FetchProperties
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();
        if ($searchQuery->fillProperties === false) {
            return;
        }

        $mainEntityClassName = $searchQuery->mainEntityClassName;

        if (in_array(PropertiesTrait::class, class_uses($mainEntityClassName), true)) {
            /** @var ResultResponse $response */
            $response = &$e->response;
            $entities = $response->entities;
            PropertiesHelper::fillProperties($entities);
            $response->entities = $entities;
        }
    }
}
