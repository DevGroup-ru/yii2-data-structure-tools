<?php

namespace DevGroup\DataStructure\search\helpers;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\search\interfaces\Filter;
use yii\base\Object;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * Class PropertiesFilterHelper
 *
 * @package DevGroup\DataStructure\search\helpers
 */
class PropertiesFilterHelper extends Object
{
    /**
     * TODO check for correct behavior with empty $propertySelections
     *
     * @param array $propertySelections
     * @param int $returnType
     * @return mixed|\yii\db\ActiveQuery[]
     */
    public static function filterObjects($propertySelections = [], $returnType = Filter::RETURN_ALL)
    {
        $selections = [];
        $selectionsCount = count($propertySelections);
        if ($selectionsCount == 0) {
            return [];
        }
        $tags = [];
        foreach ($propertySelections as $propertyId => $propertySelection) {
            $property = Property::findById($propertyId);
            $tags[] = $property->objectTag();
            $selections[] = PropertiesHelper::getModelsByPropertyValues(
                $property,
                $propertySelection,
                Filter::RETURN_QUERY
            );
        }

        $firstElement = array_shift($selections);
        $prepareState = array_reduce(
            $selections,
            function ($result, $item) {
                $objects = array_intersect(array_keys($result), array_keys($item));
                $newResult = [];
                foreach ($objects as $object) {
                    $newResult[$object] = $result[$object]->union($item[$object], true);
                }
                return $newResult;
            },
            $firstElement
        );

        /** @var ActiveQuery[] $prepareState */
        foreach ($prepareState as $className => $query) {
            /**
             * Old magic here was not working in MySQL 5.7
             * Now we gather ids and ask for models using subquery
             */
            /** @var ActiveRecord $className */
            $prepareState[$className] = $className::find()
                ->select('`t`.`id`')
                ->from(['t' => $query])
                ->addGroupBy('t.id')
                ->having(
                    "count(`t`.`id`)=" . (int)$selectionsCount
                );
        }

        switch ($returnType) {
            case Filter::RETURN_COUNT:
                foreach ($prepareState as $className => $item) {
                    $prepareState[$className] = $className::getDb()->cache(
                        function ($db) use ($item) {
                            return $item->count('*', $db);
                        },
                        86400,
                        new TagDependency(['tags' => ArrayHelper::merge($tags, (array)$className::commonTag())])
                    );
                }
                break;
            case Filter::RETURN_ALL:
                foreach ($prepareState as $className => $item) {
                    $prepareState[$className] = $className::getDb()->cache(
                        function ($db) use ($item, $className) {
                            //! @var ActiveRecord $className
                            return $className::find()
                                ->where(['in', 'id', $item])
                                ->all($db);
                        },
                        86400,
                        new TagDependency(['tags' => ArrayHelper::merge($tags, (array)$className::commonTag())])
                    );
                }
                break;
            case Filter::RETURN_QUERY:
                // do nothing, as we already have prepared query there
        }

        return $prepareState;
    }
}
