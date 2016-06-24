<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\models\PropertyStorage;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/**
 * Class PropertyStorageHelper is helepr class for PropertyStorage
 * @package DevGroup\DataStructure\helpers
 */
class PropertyStorageHelper
{
    /**
     * @var \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage[]
     */
    private static $storageHandlers = null;

    /**
     * @var array[]
     */
    private static $applicablePropertyModelStorageIds = [];

    /**
     * @return \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage[] PropertyStorage indexed by PropertyStorage.id
     */
    public static function storageHandlers()
    {
        if (self::$storageHandlers === null) {
            self::$storageHandlers = Yii::$app->cache->lazy(function () {
                $rows = PropertyStorage::find()
                    ->asArray()
                    ->all();
                return ArrayHelper::map($rows, 'id', function ($item) {
                    return new $item['class_name']($item['id']);
                });
            }, 'StorageHandlers', 86400, PropertyStorage::commonTag());
        }
        return self::$storageHandlers;
    }

    /**
     * @return \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage[] PropertyStorage indexed by PropertyStorage.id
     */
    public static function getHandlersForModel(ActiveRecord $model)
    {
        $className = get_class($model);
        if (isset(self::$applicablePropertyModelStorageIds[$className]) === false) {
            $apmId = ApplicablePropertyModels::find()
                ->select('id')
                ->where(['class_name' => $className])
                ->scalar();
            if ($apmId === false) {
                return [];
            }
            $pgIds = PropertyGroup::find()
                ->select('id')
                ->where(['applicable_property_model_id' => $apmId])
                ->column();
            if (empty($pgIds) === true) {
                return [];
            }
            self::$applicablePropertyModelStorageIds[$className] = Property::find()
                ->from(Property::tableName() . ' p')
                ->distinct(true)
                ->select('storage_id')
                ->join('JOIN', PropertyPropertyGroup::tableName() . ' pg', 'p.id = pg.property_id AND pg.property_group_id IN (' . implode(',', $pgIds) . ')')
                ->column();
        }
        $storageHandlers = static::storageHandlers();
        $result = [];
        foreach (self::$applicablePropertyModelStorageIds[$className] as $id) {
            if (isset($storageHandlers[$id]) === true) {
                $result[] = $storageHandlers[$id];
            }
        }
        return $result;
    }

    /**
     * @param string $className
     */
    public static function clearHandlersForClass($className)
    {
        unset(static::$applicablePropertyModelStorageIds[$className]);
    }

    /**
     * Returns AbstractPropertyStorage instance by PropertyStorage.id
     * @param integer $id
     * @return \DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage
     * @throws ServerErrorHttpException
     */
    public static function storageById($id)
    {
        $handlers = self::storageHandlers();
        if (isset($handlers[$id])) {
            return $handlers[$id];
        } else {
            throw new ServerErrorHttpException("Storage handler with id $id not found.");
        }
    }

    /**
     * Return a property storage id by storage class name.
     * @param string $className
     * @return int|null|string
     */
    public static function storageIdByClass($className)
    {
        foreach (self::storageHandlers() as $id => $handler) {
            if ($handler instanceof $className) {
                return $id;
            }
        }
        return null;
    }
}
