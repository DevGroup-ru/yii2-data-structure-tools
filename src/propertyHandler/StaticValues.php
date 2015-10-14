<?php

namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\DataStructure\models\Property;

class StaticValues extends AbstractPropertyHandler
{
    /**
     * Forces property to be of integer data type
     * @param \DevGroup\DataStructure\models\Property $property
     * @param                                         $insert
     *
     * @return bool
     */
    public function beforePropertyModelSave(Property &$property, $insert)
    {
        // static values are forced to be of integer data type
        $property->data_type = Property::DATA_TYPE_INTEGER;
        return parent::beforePropertyModelSave($property, $insert);
    }

    protected function getValidationRules(Property $property)
    {
        // TODO: Implement getValidationRules() method.
    }

    public function render($model, $attribute, $case)
    {
        // TODO: Implement render() method.
    }
}