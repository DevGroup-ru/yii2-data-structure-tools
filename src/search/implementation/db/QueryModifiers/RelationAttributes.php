<?php

namespace DevGroup\DataStructure\search\implementation\db\QueryModifiers;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyStorage;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use DevGroup\DataStructure\propertyStorage\TableInheritance;
use DevGroup\DataStructure\search\base\SearchEvent;
use DevGroup\DataStructure\traits\PropertiesTrait;
use yii;
use yii\db\ActiveQuery;

class RelationAttributes
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();
        if (count($searchQuery->relationAttributes) === 0) {
            return;
        }

        /** @var yii\db\ActiveQuery $activeQuery */
        $activeQuery = &$e->params['activeQuery'];
        $counter = 1;
        foreach ($searchQuery->relationAttributes as $relation => $attributes) {
            $r = "relAttr_$counter";
            $activeQuery->innerJoinWith(
                "$relation $r",
                false
            );
            foreach ($attributes as $key => $value) {
                if (mb_strpos($key, '.') === false) {
                    $key = "$r.$key";
                }
                $activeQuery->andWhere([$key => $value]);
            }
        }
        //! @todo add relation properties support here
    }


}
