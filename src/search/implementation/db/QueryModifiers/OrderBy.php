<?php

namespace DevGroup\DataStructure\search\implementation\db\QueryModifiers;

use DevGroup\DataStructure\search\base\SearchEvent;

class OrderBy
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();
        if ($searchQuery->order) {
            /** @var \yii\db\ActiveQuery $activeQuery */
            $activeQuery = &$e->params['activeQuery'];
            $activeQuery->orderBy($searchQuery->order);
        }

    }
}
