<?php

namespace DevGroup\DataStructure\propertyStorage;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

abstract class AbstractPropertyStorage
{
    public $storageId = null;

    public function __construct($storageId)
    {
        $this->storageId = intval($storageId);
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

    /**
     * @return string Returns class name
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * @param ActiveRecord[] $models
     *
     * @return boolean
     */
    abstract public function storeValues(&$models);
}