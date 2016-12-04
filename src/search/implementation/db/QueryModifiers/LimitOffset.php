<?php

namespace DevGroup\DataStructure\search\implementation\db\QueryModifiers;

use DevGroup\DataStructure\search\base\SearchEvent;
use yii;

class LimitOffset
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();

        /** @var yii\db\ActiveQuery $activeQuery */
        $activeQuery = &$e->params['activeQuery'];
        if ($searchQuery->limit) {
            $activeQuery
                ->limit($searchQuery->limit);
        }
        if ($searchQuery->offset) {
            $activeQuery
                ->offset($searchQuery->offset);
        }

    }
}
