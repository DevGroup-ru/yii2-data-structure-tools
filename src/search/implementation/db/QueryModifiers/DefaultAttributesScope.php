<?php

namespace DevGroup\DataStructure\search\implementation\db\QueryModifiers;

use DevGroup\DataStructure\search\base\SearchEvent;
use yii;

class DefaultAttributesScope
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();


        $mainEntityClassName = $searchQuery->mainEntityClassName;

        /** @var yii\db\ActiveQuery $activeQuery */
        $activeQuery = &$e->params['activeQuery'];
        $attributes = $mainEntityClassName::defaultAttributesScope();
        foreach ($attributes as $name => $value) {
            if (mb_strpos($name, '.') === false) {
                $name = $mainEntityClassName::tableName() . '.' . $name;
            }
            $activeQuery->andWhere([$name=>$value]);
        }

    }
}
