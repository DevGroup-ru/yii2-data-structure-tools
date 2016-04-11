<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use Yii;
use yii\caching\ChainedDependency;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class EAV extends AbstractPropertyStorage
{


    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        /** @var \yii\db\Command $command */
        $query = new Query();
        $query->select('*')
            ->from($firstModel->eavTable())
            ->where(PropertiesHelper::getInCondition($models))
            ->orderBy([
                'model_id' => SORT_ASC,
                'sort_order' => SORT_ASC,
            ]);

        $values = $query->createCommand($firstModel->getDb())
            ->queryAll();

        $values = ArrayHelper::map(
            $values,
            'id',
            function ($item) {
                return $item;
            },
            'model_id'
        );

        foreach ($models as &$model) {
            if (isset($values[$model->id])) {
                $groupedByProperty = ArrayHelper::map(
                    $values[$model->id],
                    'id',
                    function ($item) {
                        return $item;
                    },
                    'property_id'
                );

                foreach ($groupedByProperty as $propertyId => $propertyRows) {
                    /** @var Property $property */
                    $property = Property::findById($propertyId);

                    $key = $property->key;

                    $column = static::dataTypeToEavColumn($property->data_type);

                    $value = array_reduce(
                        $propertyRows,
                        function ($carry, $item) use ($column, $property) {
                            $value = Property::castValueToDataType($item[$column], $property->data_type);
                            $carry[] = $value;
                            return $carry;
                        },
                        []
                    );

                    if ($property->allow_multiple_values === false) {
                        $value = reset($value);
                    }
                    $model->$key = $value;
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAllProperties(&$models)
    {
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        /** @var \yii\db\Command $command */
        $command = $firstModel->getDb()->createCommand()
            ->delete($firstModel->eavTable(), PropertiesHelper::getInCondition($models));

        $command->execute();
    }

    /**
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     *
     * @return bool
     * @throws \Exception
     * @throws bool
     */
    public function storeValues(&$models)
    {
        if (count($models) === 0) {
            return true;
        }

        $insertRows = [];
        $deleteRows = [];

        /** @var ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);
        foreach ($models as $model) {
            foreach ($model->changedProperties as $propertyId) {
                /** @var Property $propertyModel */
                $propertyModel = Property::findById($propertyId);
                if ($propertyModel === null) {
                    continue;
                }
                if ($propertyModel->storage_id === $this->storageId) {
                    if (isset($deleteRows[$model->id])) {
                        $deleteRows[$model->id][] = $propertyId;
                    } else {
                        $deleteRows[$model->id] = [$propertyId];
                    }
                    $newRows = $this->saveModelPropertyRow($model, $propertyModel);
                    foreach ($newRows as $row) {
                        $insertRows[] = $row;
                    }
                }
            }
        }

        if (count($deleteRows) > 0) {
            if (count($deleteRows) > 1) {
                $condition = ['OR'];
                foreach ($deleteRows as $modelId => $propertyIds) {
                    $condition = array_merge($condition, [['model_id' => $modelId, 'property_id' => $propertyIds]]);
                }
            } else {
                $condition = [];
                foreach ($deleteRows as $modelId => $propertyIds) {
                    $condition = ['model_id' => $modelId, 'property_id' => $propertyIds];
                }
            }
            $firstModel->getDb()->createCommand()->delete($firstModel->eavTable(), $condition)->execute();
        }

        if (count($insertRows) === 0) {
            return true;
        }

        $cmd = $firstModel->getDb()->createCommand();
        return $cmd
            ->batchInsert(
                $firstModel->eavTable(),
                [
                    'model_id', 'property_id', 'sort_order', 'value_integer', 'value_float', 'value_string', 'value_text'
                ],
                $insertRows
            )->execute() > 0;

    }

    /**
     * @param ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param Property                                                    $propertyModel
     *
     * @return array
     */
    private function saveModelPropertyRow(ActiveRecord $model, Property $propertyModel)
    {
        $modelId = $model->id;
        $propertyId = $propertyModel->id;

        $key = $propertyModel->key;
        $values = (array) $model->$key;
        if (count($values) === 0) {
            return true;
        }

        $valueField = static::dataTypeToEavColumn($propertyModel->data_type);

        $rows = [];

        foreach ($values as $index => $value) {
            $rows[] = [
                $modelId,
                $propertyId,
                $index,
                $valueField === 'value_integer' ? (int) $value : 0,
                $valueField === 'value_float' ? (float) $value : 0,
                $valueField === 'value_string' ? $value : '',
                $valueField === 'value_text' ? $value : '',
            ];
        }

        return $rows;
    }

    /**
     * Returns EAV column by property data type.
     *
     * @param integer $type
     *
     * @return string
     */
    public static function dataTypeToEavColumn($type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return 'value_float';
                break;

            case Property::DATA_TYPE_BOOLEAN:
            case Property::DATA_TYPE_INTEGER:
                return 'value_integer';
                break;

            case Property::DATA_TYPE_TEXT:
            case Property::DATA_TYPE_PACKED_JSON:
                return 'value_text';
                break;

            case Property::DATA_TYPE_STRING:
            default:
                return 'value_string';
                break;
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteProperties($models, $propertyIds)
    {
        foreach ($models as $model) {
            $model->getDb()
                ->createCommand()
                ->delete($model->eavTable(), ['model_id' => $model->id, 'property_id' => (array)$propertyIds])
                ->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public function afterPropertyDelete(Property &$property)
    {
        $classNames = static::getApplicablePropertyModelClassNames($property->id);
        foreach ($classNames as $className) {
            $className::getDb()
                ->createCommand()
                ->delete(
                    $className::eavTable(),
                    ['property_id' => $property->id]
                )
                ->execute();
        }
    }

    /**
     * @inheritdoc
     */
    public static function getPropertyValuesByParams(
        $propertyId,
        $params = '',
        $customDependency = null,
        $customKey = ''
    ) {
        $property = Property::findById($propertyId);
        $column = static::dataTypeToEavColumn($property->data_type);
        $params = static::prepareParams($params, $column);
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $queries = [];
        $keys = [$customKey, 'PropertyValues', 'Property', $propertyId, Json::encode($params)];
        $tags = [$property->objectTag()];
        foreach ($classNames as $className) {
            $query = new Query();
            $query->select($column)->from($className::eavTable())->where($params);
            $queries[] = $query;
            $keys[] = $className;
            $tags[] = $className::commonTag();
        }
        if (is_null($customDependency)) {
            $dependency = new TagDependency(['tags' => $tags]);
        } elseif (is_string($customDependency)) {
            $tags[] = $customDependency;
            $dependency = new TagDependency(['tags' => $tags]);
        } else {
            $dependency = new ChainedDependency(
                ['dependencies' => [$customDependency, new TagDependency(['tags' => $tags])]]
            );
        }
        $query = self::unionQueriesToOne($queries);
        sort($keys);
        return Yii::$app->cache->lazy(
            function () use ($query) {
                return $query->column();
            },
            'EAVPV_' . md5(Json::encode($keys)),
            86400,
            $dependency
        );

    }

    /**
     * @inheritdoc
     */
    public static function getModelsByPropertyValues(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL,
        $customDependency = null
    ) {
        $result = $returnType === self::RETURN_COUNT ? 0 : [];
        $property = Property::findById($propertyId);
        $tags = [$property->objectTag()];
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $column = static::dataTypeToEavColumn($property->data_type);
        foreach ($classNames as $className) {
            $eavTable = $className::eavTable();
            $tmpQuery = $className::find()->innerJoin(
                "$eavTable  EAV",
                $className::tableName() . '.id= EAV.model_id'
            )->andWhere(
                [
                    'EAV.property_id' => $propertyId,
                    'EAV.' . $column => $values,
                ]
            )->addGroupBy($className::primaryKey());
            if (is_null($customDependency)) {
                $dependency = new TagDependency(['tags' => ArrayHelper::merge($tags, (array)$className::commonTag())]);
            } elseif (is_string($customDependency)) {
                $dependency = new TagDependency(
                    ['tags' => ArrayHelper::merge($tags, (array)$className::commonTag(), (array)$customDependency)]
                );
            } else {
                $dependency = new ChainedDependency(
                    [
                        'dependencies' => [
                            $customDependency,
                            new TagDependency(['tags' => ArrayHelper::merge($tags, (array)$className::commonTag())]),
                        ],
                    ]
                );
            }
            $result = static::valueByReturnType($returnType, $tmpQuery, $result, $className, $dependency);
        }
        return $result;
    }
}
