<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\models\StaticValueTranslation;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\caching\ChainedDependency;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class StaticValues extends AbstractPropertyStorage
{
    public static $type = Property::TYPE_VALUES_LIST;
    public static $multipleMode = Property::MODE_ALLOW_ALL;

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
        $static_values_rows = static::getValues($firstModel::className(), ArrayHelper::getColumn($models, 'id'));

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
    public static function getPropertyValuesByParams(
        $propertyId,
        $params = '',
        $customDependency = null,
        $customKey = '',
        $cacheLifetime = 86400
    )
    {
        $column = 'name';
        $params = static::prepareParams($params, $column);
        $keys = [$customKey, 'PropertyValues', 'Property', $propertyId, Json::encode($params), Yii::$app->language];
        $tags = [NamingHelper::getObjectTag(Property::className(), $propertyId)];
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

        sort($keys);
        return Yii::$app->cache->lazy(
            function () use ($column, $params, $propertyId) {
                return (new Query())->select($column)->from(StaticValueTranslation::tableName())->distinct()->where(
                    $params
                )->innerJoin(StaticValue::tableName())->andWhere(['property_id' => $propertyId])->column();
            },
            'SVPV_' . md5(Json::encode($keys)),
            $cacheLifetime,
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
        $customDependency = null,
        $cacheLifetime = 86400
    )
    {
        $result = $returnType === self::RETURN_COUNT ? 0 : [];
        $classNames = static::getApplicablePropertyModelClassNames($propertyId);
        $tags = [NamingHelper::getObjectTag(Property::className(), $propertyId)];
        $column = 'name';
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
            $dependency = static::dependencyHelper(
                $customDependency,
                ArrayHelper::merge($tags, (array)$className::commonTag())
            );
            $result = static::valueByReturnType(
                $returnType,
                $tmpQuery,
                $result,
                $className,
                $dependency,
                $cacheLifetime
            );
        }
        return $result;
    }

    public static function modelIdsQuery($modelClass, $propertyId, $values)
    {
        $cacheKey = __CLASS__ . ':' . __FUNCTION__ . ':' . static::hashPropertyValues($propertyId, $values);
        $cached = Yii::$app->cache->get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }
        $property = Property::findById($propertyId);

        $values = array_map(
            function ($val) use ($property) {
                return Property::castValueToDataType($val, $property->data_type);
            },
            $values
        );

        $q = (new Query())
            ->select('model_id')
            ->from($modelClass::staticValuesBindingsTable())
            ->where(['in', 'static_value_id', $values,])
            ->groupBy('model_id');
        Yii::$app->cache->set(
            $cacheKey,
            $q,
            86400,
            new TagDependency([
                'tags' => [
                    $modelClass::commonTag(),
                    Property::commonTag(),
                ]
            ])
        );
        return $q;
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

    /**
     * @inheritdoc
     */
    public static function getModelIdsByValues(
        $modelClass,
        $selections,
        $customDependency = null,
        $cacheLifetime = 86400
    )
    {
        if(count($selections) == 0) {
            return false;
        }
        $keys = [$modelClass, 'Property', Json::encode($selections), Yii::$app->language];
        $tags = [NamingHelper::getCommonTag($modelClass)];
        foreach ($selections as $propertyId) {
            $tags[] = NamingHelper::getObjectTag(Property::class, $propertyId);
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
        /** @var ActiveRecord | HasProperties | PropertiesTrait $model */
        $model = new $modelClass;
        $table = $model->staticValuesBindingsTable();
        $all = Yii::$app->cache->lazy(
            function () use ($table, $selections) {
                $all = [];
                $q = (new Query())->from($table)->select('model_id')->distinct(true);
                $start = true;
                $storageId = PropertyStorageHelper::storageIdByClass(static::class);
                foreach ($selections as $propertyId => $values) {
                    $property = Property::findById($propertyId);
                    if ($property->storage_id !== $storageId) {
                        continue;
                    }
                    $res = $q->where(['static_value_id' => $values])->column();
                    if (true === $start) {
                        $all = $res;
                    } else {
                        $all = array_intersect($all, $res);
                    }
                    $start = false;
                }
                if ($start) {
                    return false;
                }
                return $all;
            },
            __METHOD__ . md5(implode(':', $keys)),
            $cacheLifetime,
            $dependency
        );
        return $all;
    }

    /**
     * @inheritdoc
     */
    public static function filterFormSet($modelClass, $props, $customDependency = null, $cacheLifetime = 86400)
    {
        $keys = [$modelClass, 'Property', Json::encode($props), Yii::$app->language];
        $tags = [NamingHelper::getCommonTag($modelClass)];
        foreach ($props as $propertyId) {
            $tags[] = NamingHelper::getObjectTag(Property::className(), $propertyId);
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
        /** @var ActiveRecord | HasProperties | PropertiesTrait $model */
        $model = new $modelClass;
        $table = $model->staticValuesBindingsTable();
        $data = Yii::$app->cache->lazy(
            function () use ($props, $table) {
                $values = StaticValue::find()
                    ->select(['property_id', 'id'])
                    ->where(['property_id' => $props])
                    ->asArray(true)
                    ->all();
                $availIds = array_column($values, 'id');
                $set = (new Query())
                    ->from($table)
                    ->select('static_value_id')
                    ->where(['static_value_id' => $availIds])
                    ->distinct(true)
                    ->column();
                $data = [];
                foreach ($values as $row) {
                    if (false === in_array($row['id'], $set)) {
                        continue;
                    }
                    if (false === isset($data[$row['property_id']])) {
                        $data[$row['property_id']] = [$row['id'] => $row['defaultTranslation']['name']];
                    } else {
                        $data[$row['property_id']][$row['id']] = $row['defaultTranslation']['name'];
                    }
                }
                return $data;
            },
            __METHOD__ . md5(implode(':', $keys)),
            $cacheLifetime,
            $dependency
        );
        return $data;
    }

    public static function getFrontendValues($className, $ids, $languageId = null) {
        $svt = [];
        $values = static::getValues($className, $ids);
        foreach ($values as $modelId => $properties) {
            foreach ($properties as $propertyId => $staticValues) {
                foreach ($staticValues as $index => $staticValueId) {
                    if (!isset($svt[$staticValueId])) {
                        $svt[$staticValueId] = StaticValueTranslation::find()
                            ->select(['name'])
                            ->where(['model_id' => $staticValueId])
                            ->scalar();
                    }
                    $values[$modelId][$propertyId][$index] = $svt[$staticValueId];
                }
            }
        }
        return $values;
    }

    protected static function getValues($className, $ids, $languageId = null)
    {
        sort($ids);
        $cacheKey = $className::tableName() . ':' . implode(',', $ids) . "-static_values";
        $tags = [];
        foreach ($ids as $id) {
            $tags[] = NamingHelper::getObjectTag($className, $id);
        }
        $static_values_rows = Yii::$app->cache->lazy(function () use ($className, $ids) {
            $query = new \yii\db\Query();
            $staticValuesBindingTable = $className::staticValuesBindingsTable();
            $rows = $query
                ->select(
                    [
                        "$staticValuesBindingTable.model_id",
                        "$staticValuesBindingTable.static_value_id",
                        "$staticValuesBindingTable.sort_order",
                        "{{static_value}}.property_id",
                    ]
                )
                ->from($staticValuesBindingTable)
                ->innerJoin(StaticValue::tableName(), "$staticValuesBindingTable.static_value_id = {{static_value}}.id")
                ->where(['model_id' => $ids])
                ->orderBy(
                    [
                        "$staticValuesBindingTable.model_id" => SORT_ASC,
                        "{{static_value}}.property_id" => SORT_ASC,
                        "$staticValuesBindingTable.sort_order" => SORT_ASC
                    ]
                )
                ->all($className::getDb());
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
        }, $cacheKey, 86400, $tags);
        return $static_values_rows;
    }
}
