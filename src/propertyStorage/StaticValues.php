<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use Yii;
use yii\helpers\ArrayHelper;

class StaticValues extends AbstractPropertyStorage
{

    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        $static_values_rows = Yii::$app->cache->lazy(function () use ($firstModel, $models) {
            $query = new \yii\db\Query();
            $staticValuesBindingTable = $firstModel->staticValuesBindingsTable();
            $rows = $query
                ->select([
                    "$staticValuesBindingTable.model_id",
                    "$staticValuesBindingTable.static_value_id",
                    "$staticValuesBindingTable.sort_order",
                    "{{static_value}}.property_id",
                ])
                ->from($firstModel->staticValuesBindingsTable())
                ->innerJoin(StaticValue::tableName(), "$staticValuesBindingTable.static_value_id = {{static_value}}.id")
                ->where(PropertiesHelper::getInCondition($models))
                ->orderBy(["$staticValuesBindingTable.model_id" => SORT_ASC, "{{static_value}}.property_id" => SORT_ASC, "$staticValuesBindingTable.sort_order" => SORT_ASC])
                ->all($firstModel->getDb());

            $result = [];

            foreach ($rows as $row) {
                $modelId = $row['model_id'];

                if (isset($result[$modelId]) === false) {
                    $result[$modelId] = [];
                }
                $propertyId = $row['property_id'];
                if (isset($result[$modelId][$propertyId]) === false) {
                    $result[$modelId][$propertyId] = [];
                }
                $result[$modelId][$propertyId][] = $row['static_value_id'];
            }

            return $result;
        }, PropertiesHelper::generateCacheKey($models, 'static_values'), 86400, $firstModel->commonTag());

        // fill models with static values
        $modelIdToArrayIndex = PropertiesHelper::idToArrayIndex($models);

        foreach ($static_values_rows as $modelId => $propertyRows) {
            $model = &$models[$modelIdToArrayIndex[$modelId]];

            foreach ($propertyRows as $propertyId => $values) {
                $property = Property::findById($propertyId);
                $key = $property->key;
                $type = $property->data_type;

                array_walk(
                    $values,
                    function (&$value) use ($type) {
                        $value = intval($value);
                    }
                );

                if ($property->allow_multiple_values === false) {
                    $values = reset($values);
                }
                $model->$key = $values;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAllProperties(&$models)
    {
        /** @var \yii\db\Command $command */
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        $command = $firstModel->getDb()->createCommand()
            ->delete($firstModel->staticValuesBindingsTable(), PropertiesHelper::getInCondition($models));

        $command->execute();
    }

    /**
     * @inheritdoc
     */
    public function storeValues(&$models)
    {
        $deleteModelIds = [];
        $insertRows = [];
        foreach ($models as $model) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\DataStructure\traits\PropertiesTrait $model */
            $model->ensurePropertiesAttributes();
            $staticValuesChanged = false;

            $modelStaticValuesPairs = [];

            foreach ($model->propertiesAttributes as $propertyId => $key) {
                $property = Property::findById($propertyId);
                if ($property->storage_id === $this->storageId) {
                    // check if this property changed
                    if (in_array($propertyId, $model->changedProperties)) {
                        $staticValuesChanged = true;
                    }

                    $values = (array) $model->$key;
                    $counter = 0;
                    foreach ($values as $value) {
                        $modelStaticValuesPairs[] = [$model->id, $value, $counter++];
                    }
                }
            }
            if ($staticValuesChanged === true) {
                // add model id to delete list
                $deleteModelIds[] = $model->id;

                // add all it's static values to add list
                foreach ($modelStaticValuesPairs as $pair) {
                    $insertRows[] = $pair;
                }
            }
        }
        if (count($deleteModelIds) > 0) {
            /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
            $firstModel = reset($models);
            /** @var \yii\db\Connection $db */
            $db = $firstModel->getDb();
            $db->transaction(function (\yii\db\Connection $db) use ($deleteModelIds, $insertRows, $firstModel) {
                $db
                    ->createCommand()
                    ->delete(
                        $firstModel->staticValuesBindingsTable(),
                        [
                            'model_id' => $deleteModelIds,
                        ]
                    )->execute();

                $db
                    ->createCommand()
                    ->batchInsert(
                        $firstModel->staticValuesBindingsTable(),
                        ['model_id', 'static_value_id', 'sort_order'],
                        $insertRows
                    )->execute();
            });
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function beforePropertyValidate(Property &$property)
    {
        $property->data_type = Property::DATA_TYPE_INTEGER;
        return true;
    }
}
