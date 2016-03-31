<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use phpDocumentor\Reflection\DocBlock\Tag\PropertyReadTag;
use Yii;
use yii\base\Exception;
use yii\base\UnknownPropertyException;
use yii\caching\TagDependency;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;
use yii\web\ServerErrorHttpException;

class PropertiesHelper
{

    /**
     * @var array Hash map of className to applicable_property_models.id
     */
    public static $applicablePropertyModels = null;

    /**
     * @param bool|false $forceRefresh
     *
     * @return array
     */
    private static function retrieveApplicablePropertyModels($forceRefresh = false)
    {
        if (static::$applicablePropertyModels === null || $forceRefresh === true) {
            if ($forceRefresh === true) {
                Yii::$app->cache->delete('ApplicablePropertyModels');
            }

            static::$applicablePropertyModels = Yii::$app->cache->lazy(function () {
                $query = new \yii\db\Query();
                $rows = $query
                    ->select(['id', 'class_name'])
                    ->from(ApplicablePropertyModels::tableName())
                    ->all();
                array_walk($rows, function (&$item) {
                    $item['id'] = intval($item['id']);
                });
                return ArrayHelper::map(
                    $rows,
                    'class_name',
                    'id'
                );
            }, 'ApplicablePropertyModels', 86400);
        }
        return static::$applicablePropertyModels;
    }

    /**
     * Returns id of property_group_models record for requested classname
     *
     * @param string $className
     * @param bool|false $forceRefresh
     *
     * @return integer
     * @throws \yii\base\Exception
     */
    public static function applicablePropertyModelId($className, $forceRefresh = false)
    {
        self::retrieveApplicablePropertyModels($forceRefresh);

        if (isset(self::$applicablePropertyModels[$className])) {
            return self::$applicablePropertyModels[$className];
        } else {
            throw new Exception('Property group model record not found for class: ' . $className);
        }
    }

    /**
     * Returns class name of Model for which property or property_group model record is associated
     *
     * @param string $id
     * @param bool|false $forceRefresh
     *
     * @return string|false
     */
    public static function classNameForApplicablePropertyModelId($id, $forceRefresh = false)
    {
        self::retrieveApplicablePropertyModels($forceRefresh);
        return array_search($id, self::$applicablePropertyModels);
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

        $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));

        Yii::beginProfile('Fill properties for models');

        foreach ($storageHandlers as $storage) {
            Yii::beginProfile('Fill properties: ' . $storage->className());

            $storage->fillProperties($models);

            Yii::endProfile('Fill properties: ' . $storage->className());
        }
        foreach ($models as $model) {
            $model->changedProperties = [];
            $model->propertiesValuesChanged = false;
        }
        Yii::endProfile('Fill properties for models');
        return $models;
    }

    /**
     * Generates cache key based on models array, model table name and postfix
     *
     * @param \yii\db\ActiveRecord[] $models
     * @param string $postfix
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
        $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));

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

        $tags = [
            PropertyGroup::commonTag(),
        ];
        foreach ($models as &$model) {
            $tags[] = $model->objectTag();
        }

        $binding_rows = Yii::$app->cache->lazy(function () use ($firstModel, $models) {
            $query = new Query();

            $query
                ->select(['model_id', 'property_group_id'])
                ->from($firstModel->bindedPropertyGroupsTable())
                ->where(PropertiesHelper::getInCondition($models))
                ->orderBy(['sort_order' => SORT_ASC]);

            return $query
                ->all($firstModel->getDb());

        }, static::generateCacheKey($models, 'property_groups_ids'), 86400, $tags);

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

        $inArray = array_filter(
            ArrayHelper::getColumn($models, 'id', false),
            function ($val) {
                return $val !== null;
            }
        );
        return $inArray === [] ? [] : ['model_id' => $inArray];
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
            TagDependency::invalidate($model->getTagDependencyCacheComponent(), [$model->objectTag()]);
        }
        return true;
    }

    /**
     * @param \yii\db\ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[] $models
     * @param PropertyGroup $propertyGroup
     * @return bool
     */
    public static function unbindGroupFromModels(&$models, PropertyGroup $propertyGroup)
    {
        foreach ($models as $model) {
            if (in_array($propertyGroup->id, $model->propertyGroupIds) === false) {
                // maybe it'll be better to throw special exception in such case
                return false;
            }
            $query = (new Query())
                ->select('property_id')
                ->from(PropertyPropertyGroup::tableName())
                ->where(
                    [
                        'property_group_id' => $propertyGroup->id,
                    ]
                );
            $subQuerySql = (new Query())
                ->select('property_id')
                ->from($model->bindedPropertyGroupsTable() . ' opg')
                ->innerJoin(
                    PropertyPropertyGroup::tableName() . ' ppg',
                    'opg.property_group_id = ppg.property_group_id'
                )
                ->groupBy('property_id')
                ->having('COUNT(*) = 1')
                ->where(
                    [
                        'model_id' => $model->id,
                    ]
                )->createCommand()->getRawSql();
            $propertyIdsToDelete = $query->andWhere('property_id IN (' . $subQuerySql . ')')->column();
            $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));
            foreach ($storageHandlers as $handler) {
                $handler->deleteProperties($models, $propertyIdsToDelete);
            }
            $model->getDb()->createCommand()
                ->delete(
                    $model->bindedPropertyGroupsTable(),
                    [
                        'model_id' => $model->id,
                        'property_group_id' => $propertyGroup->id,
                    ]
                )
                ->execute();
            TagDependency::invalidate($model->getTagDependencyCacheComponent(), [$model->objectTag()]);
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

        $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));

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

    /**
     * @param \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param string $attribute
     * @return Property | null
     */
    public static function getPropertyModel($model, $attribute)
    {
        $propertyId = array_search($attribute, $model->propertiesAttributes);
        if ($propertyId === false) {
            return null;
        }
        return Property::findById($propertyId, false);
    }

    /**
     * Get available property groups by class name.
     * @param string $className
     * @return array
     * @throws Exception
     */
    public static function getAvailablePropertyGroupsList($className)
    {
        $applicablePropertyModelId = PropertiesHelper::applicablePropertyModelId($className);
        $availableGroups = Yii::$app->cache->lazy(
            function () use ($applicablePropertyModelId) {
                return ArrayHelper::map(
                    PropertyGroup::find()
                        ->where(['applicable_property_model_id' => $applicablePropertyModelId])
                        ->orderBy('sort_order ASC')
                        ->all(),
                    'id',
                    function ($model) {
                        return !empty($model->name) ? $model->name : $model->internal_name;
                    }
                );

            },
            'AvailablePropertyGroupsList: ' . $applicablePropertyModelId,
            86400,
            PropertyGroup::commonTag()
        );
        return $availableGroups;
    }

    public static function getPropertyValuesByParams(Property $property, $params = '')
    {
        $storageClass = $property->storage->class_name;
        return $storageClass::getPropertyValuesByParams($property->id, $params);
    }
}
