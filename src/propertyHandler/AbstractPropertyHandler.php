<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\models\Property;


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

    /**
     * @param \DevGroup\DataStructure\models\Property $property
     * @param bool                                    $insert
     *
     * @return bool
     */
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
     * @return string class name with namespace
     */
    public static function className()
    {
        return get_called_class();
    }
}
