<?php

namespace DevGroup\DataStructure\search\implementation\db\QueryModifiers;

use DevGroup\DataStructure\search\base\SearchEvent;
use yii;

class DefaultWith
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();


        $mainEntityClassName = $searchQuery->mainEntityClassName;

        /** @var yii\db\ActiveQuery $activeQuery */
        $activeQuery = &$e->params['activeQuery'];
        $with = $mainEntityClassName::defaultWith();
        $activeQuery->with($with);

    }
}
