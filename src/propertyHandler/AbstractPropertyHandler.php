<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use yii\base\UnknownPropertyException;
use yii\web\ServerErrorHttpException;

abstract class AbstractPropertyHandler
{

    /** @var AbstractPropertyHandler Singleton */
    public static $instance = null;

    /**
     * @return AbstractPropertyHandler Property handler instance(singleton is used)
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function afterFind($model, $attribute) {

    }

    public function afterSave($model, $attribute) {

    }

    public function beforeSave($model, $attribute) {
        return true;
    }

    abstract protected function getValidationRules(Property $property);

    abstract public function render($model, $attribute, $case);

    /**
     * @param \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param $attribute
     */
    protected function getPropertyModel($model, $attribute)
    {
        $property_id = array_search($attribute, $model->propertiesAttributes);
        if ($property_id === false) {
            throw new UnknownPropertyException("Attribute $attribute not found in model ".$model->className());
        }
        return Property::loadModel(
            $property_id,
            false,
            true,
            86400,
            new ServerErrorHttpException("Property with id $property_id not found."),
            true
        );
    }
}