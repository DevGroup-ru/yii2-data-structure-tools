<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyStorage;
use Yii;
use yii\base\Exception;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class PropertiesHelper
{
    /**
     * @var \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage[]
     */
    private static $_storageHandlers = null;

    /**
     * @var array Hash map of className to property_group_model.id
     */
    private static $_property_group_models = null;

    /**
     * @return \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage[] PropertyStorage indexed by PropertyStorage.id
     */
    public static function storageHandlers()
    {
        if (static::$_storageHandlers === null) {
            static::$_storageHandlers = Yii::$app->cache->lazy(function () {
                $rows = PropertyStorage::find()
                    ->asArray()
                    ->all();
                return ArrayHelper::map($rows, 'id', function ($item) {
                    return new $item['class_name']($item['id']);
                });
            }, 'StorageHandlers', 86400, PropertyStorage::commonTag());
        }
        return static::$_storageHandlers;
    }

    /**
     * @param bool|false $forceRefresh
     *
     * @return array
     */
    private static function retrievePropertyGroupModels($forceRefresh = false)
    {
        if (static::$_property_group_models === null || $forceRefresh === true) {
            if ($forceRefresh === true) {
                Yii::$app->cache->delete('PropertyGroupModelsMap');
            }

            static::$_property_group_models = Yii::$app->cache->lazy(function () {
                $query = new \yii\db\Query();
                $rows = $query
                    ->select(['id', 'class_name'])
                    ->from('{{%property_group_models}}')
                    ->all();
                array_walk($rows, function (&$item) {
                    $item['id'] = intval($item['id']);
                });
                return ArrayHelper::map(
                    $rows,
                    'class_name',
                    'id'
                );
            }, 'PropertyGroupModelsMap', 86400);
        }
        return static::$_property_group_models;
    }

    /**
     * Returns id of property_group_models record for requested classname
     *
     * @param string      $className
     * @param bool|false  $forceRefresh
     *
     * @return integer
     * @throws \yii\base\Exception
     */
    public static function propertyGroupModelId($className, $forceRefresh = false)
    {
        static::retrievePropertyGroupModels($forceRefresh);

        if (isset(static::$_property_group_models[$className])) {
            return static::$_property_group_models[$className];
        } else {
            throw new Exception('Property group model record not found for class: ' . $className);
        }
    }

    /**
     * Returns class name of Model for which property group model record is associated
     *
     * @param string      $id
     * @param bool|false  $forceRefresh
     *
     * @return string|false
     */
    public static function classNameForPropertyGroupModelId($id, $forceRefresh = false)
    {
        static::retrievePropertyGroupModels($forceRefresh);
        return array_search($id, static::$_property_group_models);
    }

    /**
     * @param \yii\db\ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     * @param bool|false $forceRefresh
     */
    public static function ensurePropertiesAttributes(&$models, $forceRefresh = false)
    {
        foreach ($models as &$model) {
            $model->ensurePropertiesAttributes($forceRefresh);
        }
    }

    /**
     * @param \yii\db\ActiveRecord[] $models
     */
    public static function fillProperties(&$models)
    {
        static::fillPropertyGroups($models);
        static::ensurePropertiesAttributes($models);

        $storageHandlers = static::storageHandlers();

        Yii::beginProfile('Fill properties for models');

        foreach ($storageHandlers as $storage) {
            Yii::beginProfile('Fill properties: ' . $storage->className());

            $storage->fillProperties($models);

            Yii::endProfile('Fill properties: ' . $storage->className());
        }
        Yii::endProfile('Fill properties for models');
        return $models;
    }

    /**
     * Generates cache key based on models array, model table name and postfix
     *
     * @param \yii\db\ActiveRecord[] $models
     * @param string                 $postfix
     *
     * @return string
     */
    public static function generateCacheKey($models, $postfix = 'properties')
    {
        $ids = ArrayHelper::getColumn($models, 'id', false);
        sort($ids);
        /** @var \yii\db\ActiveRecord $first */
        $first = reset($models);
        return $first->tableName() . ':' . implode(',', $ids) . "-$postfix";
    }

    /**
     * Deletes all properties binded to $models
     *
     * @param \yii\db\ActiveRecord[] $models
     *
     * @return \yii\db\ActiveRecord[]
     */
    public static function deleteAllProperties(&$models)
    {
        $storageHandlers = static::storageHandlers();

        Yii::beginProfile('Fill properties for models');

        foreach ($storageHandlers as $handler) {
            Yii::beginProfile($handler);

            $handler->deleteAllProperties($models);

            Yii::endProfile($handler);
        }
        Yii::endProfile('Fill properties for models');
        return $models;
    }

    /**
     * Fills all $models with corresponding binded property_group_ids
     *
     * @param \yii\db\ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     *
     * @return \yii\db\ActiveRecord[]
     */
    public static function fillPropertyGroups(&$models)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        if ($firstModel->propertyGroupIds !== null) {
            // assume that we have already got them
            return;
        }

        $binding_rows = Yii::$app->cache->lazy(function () use ($firstModel, $models) {
            $query = new Query();
            return $query
                ->select(['model_id', 'property_group_id'])
                ->from($firstModel->bindedPropertyGroupsTable())
                ->where(PropertiesHelper::getInCondition($models))
                ->orderBy(['sort_order' => SORT_ASC])
                ->all($firstModel->getDb());
        }, static::generateCacheKey($models, 'property_groups_ids'), 86400, $firstModel->commonTag());

        array_walk(
            $binding_rows,
            function (&$item) {
                $item['model_id'] = intval($item['model_id']);
                $item['property_group_id'] = intval($item['property_group_id']);
            }
        );

        foreach ($models as &$model) {
            $id = $model->id;
            $model->propertyGroupIds = array_reduce(
                $binding_rows,
                function ($carry, $item) use ($id) {
                    if ($item['model_id'] === $id) {
                        $carry[] = $item['property_group_id'];
                    }
                    return $carry;
                },
                []
            );
        }

    }

    /**
     * Returns fast IN condition for $models array without PDO param binding which is not necessary for integers
     *
     * @param \yii\db\ActiveRecord[] $models
     *
     * @return string
     */
    public static function getInCondition(&$models)
    {
        return $condition = 'model_id in (' . implode(',', ArrayHelper::getColumn($models, 'id', false)) . ')';
    }

    /**
     * @param \yii\db\ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     * @param PropertyGroup $propertyGroup
     * @return bool
     */
    public static function bindGroupToModels(&$models, PropertyGroup $propertyGroup)
    {
        foreach ($models as &$model) {
            $model->ensurePropertyGroupIds();
            if (in_array($propertyGroup->id, $model->propertyGroupIds) === true) {
                // maybe it'll be better to throw special exception in such case
                return false;
            }

            $model->getDb()->createCommand()
                ->insert(
                    $model->bindedPropertyGroupsTable(),
                    [
                        'model_id' => $model->id,
                        'property_group_id' => $propertyGroup->id,
                        'sort_order' => count($model->propertyGroupIds),
                    ]
                )
                ->execute();

            $model->propertyGroupIds[] = $propertyGroup->id;

            if ($model->propertiesAttributes !== null) {
                // if there were propertiesIds filled - refresh them
                $model->ensurePropertiesAttributes(true);
            }
        }
        return true;
    }

    /**
     * @param \yii\db\ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     *
     * @return boolean
     */
    public static function storeValues(&$models)
    {
        // first filter out models that has no changed properties
        $changedModels = array_filter(
            $models,
            function ($model) {
                /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model */
                return $model->propertiesValuesChanged;
            }
        );

        $storageHandlers = static::storageHandlers();

        $result = true;

        foreach ($storageHandlers as $storageId => $storage) {
            Yii::beginProfile("Saving storage $storageId");
            $result = $storage->storeValues($changedModels) && $result;
            Yii::endProfile("Saving storage $storageId");
        }
        return $result;
    }

    /**
     * @param $models
     * @return array Array where key is model.id and value is model's index in array of $models
     */
    public static function idToArrayIndex(&$models)
    {
        $result = [];
        foreach ($models as $index => &$model) {
            $result[$model->id] = $index;
        }
        return $result;
    }
}
