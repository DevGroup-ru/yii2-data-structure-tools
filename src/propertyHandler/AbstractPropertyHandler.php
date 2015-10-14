<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\models\Property;
use yii\base\UnknownPropertyException;
use yii\web\ServerErrorHttpException;

abstract class AbstractPropertyHandler
{
    public function __construct()
    {

    }

    public function afterFind($model, $attribute)
    {

    }

    public function afterSave($model, $attribute)
    {

    }

    public function beforeSave($model, $attribute)
    {
        return true;
    }

    public function beforePropertyModelSave(Property &$property, $insert)
    {
        return true;
    }

    public function afterPropertyModelSave(Property &$property)
    {

    }

    abstract protected function getValidationRules(Property $property);

    abstract public function render($model, $attribute, $case);

    /**
     * @param \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $model
     * @param string $attribute
     * @throws UnknownPropertyException
     * @throws ServerErrorHttpException
     * @return Property
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

    public static function className()
    {
        return get_called_class();
    }


}