<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\PropertyStorage;
use Yii;
use yii\base\Exception;
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
            static::$_storageHandlers = Yii::$app->cache->lazy(function() {
                $rows = PropertyStorage::find()
                    ->asArray()
                    ->all();
                return ArrayHelper::map($rows, 'id', 'class_name');
            }, 'StorageHandlers', 86400, PropertyStorage::commonTag());
        }
        return static::$_storageHandlers;
    }

    /**
     * Returns id of property_group_models record for requested classname
     *
     * @param string     $className
     * @param bool       $forceRefresh
     *
     * @return integer
     * @throws \yii\base\Exception
     */
    public static function propertyGroupModelId($className, $forceRefresh = false)
    {
        if (static::$_property_group_models === null || $forceRefresh === true) {

            if ($forceRefresh === true) {
                Yii::$app->cache->delete('PropertyGroupModelsMap');
            }

            static::$_property_group_models = Yii::$app->cache->lazy(function() {
                $query = new \yii\db\Query();
                $rows = $query
                    ->select(['id', 'class_name'])
                    ->from('{{%property_group_models}}')
                    ->all();
                array_walk($rows, function(&$item) {
                    $item['id'] = intval($item['id']);
                });
                return ArrayHelper::map(
                    $rows,
                    'class_name',
                    'id'
                );
            }, 'PropertyGroupModelsMap', 86400);
        }

        if (isset(static::$_property_group_models[$className])) {
            return static::$_property_group_models[$className];
        } else {
            throw new Exception('Property group model record not found for class: ' . $className);
        }
    }

    /**
     * @param \yii\db\ActiveRecord[] $models
     */
    public static function fillProperties(&$models)
    {
        $storageHandlers = static::storageHandlers();

        Yii::beginProfile('Fill properties for models');

        foreach ($storageHandlers as $handler) {
            Yii::beginProfile($handler);

            $handler->fillProperties($models);

            Yii::endProfile($handler);
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
     * @param \yii\db\ActiveRecord[] $models
     *
     * @return \yii\db\ActiveRecord[]
     */
    public static function fillPropertyGroups(&$models)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        if ($firstModel->property_group_ids !== null) {
            // assume that we have already got them
            return;
        }

        $binding_rows = Yii::$app->cache->lazy(function() use($firstModel, $models) {
            $query = new \yii\db\Query();
            return $query
                ->select(['model_id', 'property_group_id'])
                ->from($firstModel->binded_property_groups_table())
                ->where(PropertiesHelper::getInCondition($models))
                ->orderBy(['sort_order' => SORT_ASC])
                ->all($firstModel->getDb());
        }, static::generateCacheKey($models, 'property_groups_ids'), 86400, $firstModel->commonTag());

        array_walk(
            $binding_rows,
            function(&$item) {
                $item['model_id'] = intval($item['model_id']);
                $item['property_group_id'] = intval($item['property_group_id']);
            }
        );

        foreach ($models as &$model) {
            $id = $model->id;
            $model->property_group_ids = array_reduce(
                $binding_rows,
                function($carry, $item) use ($id) {
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
     * Returns mapping from model id to array index
     *
     * @param \yii\db\ActiveRecord[] $models
     *
     * @return array
     */
    public static function idToArrayIndex(&$models)
    {
        $map = [];
        foreach ($models as $index => $model) {
            $map[$model->id] = $index;
        }
        return $map;
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
}