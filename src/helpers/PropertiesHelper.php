<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\propertyHandler\RelatedEntity;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\TagDependencyHelper\LazyCache;
use Yii;
use yii\base\Exception;
use yii\base\Model;
use yii\caching\TagDependency;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class PropertiesHelper
{

    /**
     * @var array Hash map of className to applicable_property_models.id
     */
    public static $applicablePropertyModels;

    /**
     * @param bool|false $forceRefresh
     *
     * @return array
     */
    private static function retrieveApplicablePropertyModels($forceRefresh = false)
    {
        if ($forceRefresh === true) {
            Yii::$app->cache->delete('ApplicablePropertyModels');
            static::$applicablePropertyModels = null;
        }
        if (static::$applicablePropertyModels === null) {
            /** @var LazyCache $cache */
            $cache = Yii::$app->cache;
            static::$applicablePropertyModels = $cache->lazy(
                function () {
                    $query = new Query();
                    $rows = $query
                        ->select(['id', 'class_name'])
                        ->from(ApplicablePropertyModels::tableName())
                        ->all();

                    array_walk(
                        $rows,
                        function (&$item) {
                            $item['id'] = (int) $item['id'];
                        }
                    );
                    return ArrayHelper::map(
                        $rows,
                        'class_name',
                        'id'
                    );
                },
                'ApplicablePropertyModels',
                86400
            );
        }
        return static::$applicablePropertyModels;
    }

    /**
     * Returns id of property_group_models record for requested classname
     *
     * @param ActiveRecord | string $class
     * @param bool|false $forceRefresh
     *
     * @return integer
     * @throws \yii\base\Exception
     */
    public static function applicablePropertyModelId($class, $forceRefresh = false)
    {
        //ability to store properties of all model heirs in the one set of tables
        if (true === method_exists($class, 'getApplicableClass')) {
            $modelClass = call_user_func([$class, 'getApplicableClass']);
        } else {
            $modelClass = is_string($class) ? $class : get_class($class);
        }
        self::retrieveApplicablePropertyModels($forceRefresh);

        if (isset(self::$applicablePropertyModels[$modelClass])) {
            return self::$applicablePropertyModels[$modelClass];
        } else {
            throw new Exception('Property group model record not found for class: ' . $modelClass);
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
        return array_search($id, self::$applicablePropertyModels, true);
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
     * @param bool $frontend
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function fillProperties(&$models, $frontend = false)
    {
        if (count($models) === 0) {
            return [];
        }
        //! @todo Add check for not retrieving properties twice
        static::fillPropertyGroups($models);
        static::ensurePropertiesAttributes($models);


        $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));

        Yii::beginProfile('Fill properties for models');

        foreach ($storageHandlers as $storage) {
            Yii::beginProfile('Fill properties: ' . $storage::className());
            if (true === $frontend && get_class($storage) === EAV::class) {
                /** @var EAV $storage */
                $storage->fillProperties($models, Yii::$app->multilingual->language_id);
            } else {
                $storage->fillProperties($models);
            }
            Yii::endProfile('Fill properties: ' . $storage::className());
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
        return $first::tableName() . ':' . implode(',', $ids) . "-$postfix";
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
     * @throws Exception
     */
    public static function fillPropertyGroups(&$models)
    {

        if (count($models) === 0) {
            return [];
        }
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        //        if ($firstModel->propertyGroupIds !== null) {
        //            // assume that we have already got them
        //            return;
        //        }

        $tags = [
            PropertyGroup::commonTag(),
        ];
        foreach ($models as $modelForTag) {
            $tags[] = $modelForTag->objectTag();
        }

        $binding_rows = Yii::$app->cache->lazy(
            function () use ($firstModel, $models) {
                $query = new Query();

                $query->select(['model_id', 'property_group_id'])->from(
                    $firstModel::bindedPropertyGroupsTable()
                )->where(PropertiesHelper::getInCondition($models))->orderBy(['sort_order' => SORT_ASC]);

                return $query->all($firstModel::getDb());

            },
            static::generateCacheKey($models, 'property_groups_ids'),
            0,
            $tags
        );

        array_walk(
            $binding_rows,
            function (&$item) {
                $item['model_id'] = (int) $item['model_id'];
                $item['property_group_id'] = (int) $item['property_group_id'];
            }
        );

        foreach ($models as &$model) {
            $id = $model->id;
            $model->propertyGroupIds = array_reduce(
                $binding_rows,
                function ($carry, $item) use ($id) {
                    if ($item['model_id'] === $id) {
                        $carry[] = (int) $item['property_group_id'];
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
     *
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

            $model->getDb()->createCommand()->insert(
                $model->bindedPropertyGroupsTable(),
                [
                    'model_id' => $model->id,
                    'property_group_id' => $propertyGroup->id,
                    'sort_order' => count($model->propertyGroupIds),
                ]
            )->execute();

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
     *
     * @return bool
     */
    public static function unbindGroupFromModels(&$models, PropertyGroup $propertyGroup)
    {
        foreach ($models as $model) {
            if (in_array($propertyGroup->id, $model->propertyGroupIds) === false) {
                // maybe it'll be better to throw special exception in such case
                return false;
            }
            $query = (new Query())->select('property_id')->from(PropertyPropertyGroup::tableName())->where(
                [
                    'property_group_id' => $propertyGroup->id,
                ]
            );
            $subQuerySql = (new Query())->select('property_id')->from(
                $model::bindedPropertyGroupsTable() . ' opg'
            )->innerJoin(
                PropertyPropertyGroup::tableName() . ' ppg',
                'opg.property_group_id = ppg.property_group_id'
            )->groupBy('property_id')->having('COUNT(*) = 1')->where(
                [
                    'model_id' => $model->id,
                ]
            )->createCommand()->getRawSql();
            $propertyIdsToDelete = $query->andWhere('property_id IN (' . $subQuerySql . ')')->column();
            $storageHandlers = PropertyStorageHelper::getHandlersForModel(reset($models));
            foreach ($storageHandlers as $handler) {
                $handler->deleteProperties($models, $propertyIdsToDelete);
            }
            $model::getDb()->createCommand()->delete(
                $model::bindedPropertyGroupsTable(),
                [
                    'model_id' => $model->id,
                    'property_group_id' => $propertyGroup->id,
                ]
            )->execute();
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
     *
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
     *
     * @return Property | null
     */
    public static function getPropertyModel($model, $attribute)
    {
        $propertyId = array_search($attribute, $model->propertiesAttributes, true);
        if ($propertyId === false) {
            return null;
        }
        return Property::findById($propertyId, false);
    }

    /**
     * Get available property groups by class name.
     *
     * @param string $className
     *
     * @return array
     * @throws Exception
     */
    public static function getAvailablePropertyGroupsList($className)
    {
        $applicablePropertyModelId = PropertiesHelper::applicablePropertyModelId($className);
        $availableGroups = Yii::$app->cache->lazy(
            function () use ($applicablePropertyModelId) {
                return ArrayHelper::map(
                    PropertyGroup::find()->where(
                        ['applicable_property_model_id' => $applicablePropertyModelId]
                    )->orderBy('sort_order ASC')->all(),
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

    public static function getPropertyValuesByParams(
        Property $property,
        $params = '',
        $customDependency = null,
        $customKey = '',
        $cacheLifetime = 86400
    )
    {
        $storageClass = $property->storage->class_name;
        return $storageClass::getPropertyValuesByParams(
            $property->id,
            $params,
            $customDependency,
            $customKey,
            $cacheLifetime
        );
    }

    public static function getModelsByPropertyValues(
        Property $property,
        $values = [],
        $returnType = AbstractPropertyStorage::RETURN_ALL
    )
    {
        $storageClass = $property->storage->class_name;
        return $storageClass::getModelsByPropertyValues($property->id, $values, $returnType);
    }

    /**
     * @param string $className
     * @param bool $throwException
     *
     * @return array
     * @throws Exception
     */
    public static function getAttributeNamesByClassName($className = '', $throwException = false)
    {
        $except = false;
        if (empty($className) === false) {
            $model = new $className;
            if ($model instanceof Model) {
                /** @var $model Model */
                return $model->attributeLabels();
            } else {
                $except = true;
            }
        }
        if ($throwException && $except) {
            throw new Exception;
        } else {
            return [];
        }
    }

    /**
     * @param Property $property
     * @param ActiveRecord $model
     *
     * @throws Exception
     */
    public static function getRelatedEntitiesByProperty($property, $model)
    {
        if ($property->handler() instanceof RelatedEntity === false) {
            throw new Exception(Yii::t('app', 'Set correct property handler'));
        }
        $handlerParam = ArrayHelper::getValue($property->params, Property::PACKED_HANDLER_PARAMS);
        $className = $handlerParam['className'];
        $sortOrder = $handlerParam['sortOrder'];
        $order = (bool) $handlerParam['order'] ? SORT_DESC : SORT_ASC;

        $primaries = $model->primaryKey();
        $primary = reset($primaries);
        return $className::find()->where([$primary => $model->{$property->key}])
            ->orderBy([$sortOrder => $order])->all();
    }
}
