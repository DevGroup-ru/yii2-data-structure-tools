<?php

namespace DevGroup\DataStructure\propertyStorage;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

abstract class AbstractPropertyStorage
{
    /** @var AbstractPropertyStorage Singleton */
    public static $instance = null;



    /**
     * @return AbstractPropertyStorage PropertyStorage handler instance(singleton is used)
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * Fills $models array models with corresponding binded properties.
     * Models in $models array should be the the same class name.
     *
     * @param ActiveRecord[] $models
     *
     * @return ActiveRecord[]
     */
    abstract public function fillProperties(&$models);

    /**
     * Removes all properties binded to models.
     * Models in $models array should be the the same class name.
     *
     * @param ActiveRecord[] $models
     *
     * @return void
     */
    abstract public function deleteAllProperties(&$models);

    public static function className()
    {
        return get_called_class();
    }
}