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

    abstract public function getValidationRules(Property $property);

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
        $propertyId = array_search($attribute, $model->propertiesAttributes);
        if ($propertyId === false) {
            throw new UnknownPropertyException("Attribute $attribute not found in model ".$model->className());
        }
        return Property::findById($propertyId);
    }

    /**
     * @return string class name with namespace
     */
    public static function className()
    {
        return get_called_class();
    }
}
