<?php


namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use yii\base\Object;
use yii\db\ActiveQuery;

class PropertiesFilterHelper extends Object
{

    public static function filterObjects($propertySelections = [], $returnType = AbstractPropertyStorage::RETURN_ALL)
    {
        $selections = [];
        $selectionsCount = count($propertySelections);
        foreach ($propertySelections as $propertyId => $propertySelection) {
            $selections[] = PropertiesHelper::getModelsByPropertyValues(
                Property::findById($propertyId),
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
                    $prepareState[$className] = $item->count();
                }
                break;
            case AbstractPropertyStorage::RETURN_ALL:
                foreach ($prepareState as $className => $item) {
                    $prepareState[$className] = $item->all();
                }
                break;
        }

        return $prepareState;
    }
}