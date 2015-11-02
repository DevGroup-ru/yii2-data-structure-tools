<?php

namespace DevGroup\DataStructure\helpers;

use DevGroup\DataStructure\models\PropertyStorage;
use Yii;
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
}
