<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\models\StaticValueTranslation;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class StaticValues extends AbstractPropertyStorage
{
    /**
     * Get static value ids sql by property id(s).
     * @param int | int[] $id
     * @return string
     */
    protected function getStaticValueIdsSql($id)
    {
        return (new Query())
            ->select('id')
            ->from(StaticValue::tableName())
            ->where(['property_id' => $id])
            ->createCommand()
            ->getRawSql();
    }

    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        $tags = [];
        foreach ($models as $model) {
            $tags[] = $model->objectTag();
        }
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
                ->orderBy([
                    "$staticValuesBindingTable.model_id" => SORT_ASC,
                    "{{static_value}}.property_id" => SORT_ASC,
                    "$staticValuesBindingTable.sort_order" => SORT_ASC
                ])
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
        }, PropertiesHelper::generateCacheKey($models, 'static_values'), 86400, $tags);

        // fill models with static values
        $modelIdToArrayIndex = PropertiesHelper::idToArrayIndex($models);

        foreach ($static_values_rows as $modelId => $propertyRows) {

            if (isset($modelIdToArrayIndex[$modelId]) && isset($models[$modelIdToArrayIndex[$modelId]])) {
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

                    $values = (array)$model->$key;
                    $counter = 0;
                    foreach ($values as $value) {
                        if (empty($value) === false) {
                            $modelStaticValuesPairs[] = [$model->id, $value, $counter++];
                        }
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
                if (empty($deleteModelIds) === false) {
                    $db
                        ->createCommand()
                        ->delete(
                            $firstModel->staticValuesBindingsTable(),
                            [
                                'model_id' => $deleteModelIds,
                            ]
                        )->execute();
                }

                if (empty($insertRows) === false) {
                    $db
                        ->createCommand()
                        ->batchInsert(
                            $firstModel->staticValuesBindingsTable(),
                            ['model_id', 'static_value_id', 'sort_order'],
                            $insertRows
                        )->execute();
                }
            });
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public static function getPropertyValuesByParams($propertyId, $params = '')
    {
        $column = 'description';
        if (is_string($params)) {
            $params = str_replace('[column]', $column, $params);
        } elseif (is_array($params)) {
            $params = Json::decode(str_replace('[column]', $column, Json::encode($params)));
        } else {
            return [];
        }
        return (new Query())->select($column)->from(StaticValueTranslation::tableName())->distinct()->where(
            $params
        )->innerJoin(StaticValue::tableName())->andWhere(['property_id' => $propertyId])->column();
    }

    /**
     * @inheritdoc
     */
    public static function getModelsByPropertyValuesParams(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL
    ) {
        $result = $returnType === self::RETURN_COUNT ? 0 : [];
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $column = 'description';
        foreach ($classNames as $className) {
            $tmpQuery = $className::find()->innerJoin(
                $className::staticValuesBindingsTable() . ' MSV',
                'MSV.model_id=' . $className::tableName() . '.id'
            )->innerJoin(StaticValue::tableName() . ' SV', 'SV.id=MSV.static_value_id')->innerJoin(
                StaticValueTranslation::tableName() . ' SVT',
                'SVT.model_id=SV.id'
            )->andWhere(
                [
                    'SV.property_id' => $propertyId,
                    "SVT.$column" => $values,
                    'SVT.language_id' => Yii::$app->multilingual->language_id,
                ]
            )->addGroupBy($className::primaryKey());
            switch ($returnType) {
                case self::RETURN_COUNT:
                    $result += $tmpQuery->count();
                    break;
                case self::RETURN_QUERY:
                    $result[] = $tmpQuery;
                    break;
                default:
                    if (!empty($tmpQuery)) {
                        $result = ArrayHelper::merge($result, $tmpQuery->all());
                    }
            }
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function beforePropertyValidate(Property &$property)
    {
        $property->data_type = Property::DATA_TYPE_INTEGER;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteProperties($models, $propertyIds)
    {
        $subQuerySql = $this->getStaticValueIdsSql($propertyIds);
        foreach ($models as $model) {
            $model->getDb()
                ->createCommand()
                ->delete(
                    $model->staticValuesBindingsTable(),
                    'model_id = \'' . (int)$model->id . '\' AND static_value_id IN (' . $subQuerySql . ')'
                )
                ->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropertyDelete(Property &$property)
    {
        $classNames = static::getApplicablePropertyModelClassNames($property->id);
        $subQuerySql = $this->getStaticValueIdsSql($property->id);
        foreach ($classNames as $className) {
            $className::getDb()
                ->createCommand()
                ->delete(
                    $className::staticValuesBindingsTable(),
                    'static_value_id IN (' . $subQuerySql . ')'
                )
                ->execute();
        }
        // This foreach is required for translation deleting
        $staticValues = StaticValue::findAll(['property_id' => $property->id]);
        foreach ($staticValues as $staticValue) {
            $staticValue->delete();
        }
    }
}
