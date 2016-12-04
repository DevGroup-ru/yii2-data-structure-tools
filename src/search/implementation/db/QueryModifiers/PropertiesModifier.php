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

class PropertiesModifier
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();
        if (count($searchQuery->properties) === 0) {
            return;
        }

        /** @var yii\db\ActiveQuery $activeQuery */
        $activeQuery = &$e->params['activeQuery'];
        foreach ($searchQuery->properties as $className => $selections) {
            $queries = static::filterObjects($className, $selections);
            foreach ($queries as $c => $q) {
                $activeQuery->andWhere(['in', 'id', $q]);
            }
        }
    }

    /**
     * @param string|\yii\db\ActiveRecord|PropertiesTrait $className
     * @param array $selections
     * @return ActiveQuery[]
     */
    protected static function filterObjects($className, $selections)
    {
        // pre-fetch properties
        Property::findByIds(array_keys($selections));
        /** @var ActiveQuery[] $queries */
        $queries = [];

        // first filter everything to find out table inheritance properties
        $tiSelections = [];
        //modelIdsQueryAtOnce
        foreach ($selections as $propertyId => $values) {
            $property = Property::findById($propertyId);
            /** @var PropertyStorage $storage */
            $storage = $property->storage();
            /** @var AbstractPropertyStorage $storageClass */
            $storageClass = $storage->class_name;
            if ($storageClass === TableInheritance::class) {
                $tiSelections[$propertyId] = $values;
            }
            unset($selections[$propertyId]);
        }
        // go through other properties
        foreach ($selections as $propertyId => $values) {
            $values = (array) $values;
            $property = Property::findById($propertyId);
            /** @var PropertyStorage $storage */
            $storage = $property->storage();
            /** @var AbstractPropertyStorage $storageClass */
            $storageClass = $storage->class_name;
            $q = $storageClass::modelIdsQuery($className, $propertyId, $values);
            if ($q !== null) {
                $queries[] = $q;
            }
        }
        // go through table inheritance
        if (count($tiSelections) > 0) {
            $queries[] = TableInheritance::modelIdsQueryAtOnce($className, $tiSelections);
        }
        return $queries;
    }
}
