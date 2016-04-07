<?php


namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use yii\base\Object;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class PropertiesFilterHelper extends Object
{

    public static function filterObjects($propertySelections = [], $returnType = AbstractPropertyStorage::RETURN_ALL)
    {
        $selections = [];
        $selectionsCount = count($propertySelections);
        $tags = [];
        foreach ($propertySelections as $propertyId => $propertySelection) {
            $property = Property::findById($propertyId);
            $tags[] = $property->objectTag();
            $selections[] = PropertiesHelper::getModelsByPropertyValues(
                $property,
                $propertySelection,
                AbstractPropertyStorage::RETURN_QUERY
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
            $prepareState[$className] = $className::find()->from(['t' => $query])->addGroupBy('t.id')->having(
                "count(t.id)=$selectionsCount"
            );
        }

        switch ($returnType) {
            case AbstractPropertyStorage::RETURN_COUNT:
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
            case AbstractPropertyStorage::RETURN_ALL:
                foreach ($prepareState as $className => $item) {
                    $prepareState[$className] = $className::getDb()->cache(
                        function ($db) use ($item) {
                            return $item->all($db);
                        },
                        86400,
                        new TagDependency(['tags' => ArrayHelper::merge($tags, (array)$className::commonTag())])
                    );
                }
                break;
        }

        return $prepareState;
    }
}