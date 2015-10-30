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

    public function getValidationRules(Property $property)
    {
        $key = $property->key;
        if ($property->allow_multiple_values) {
            return [
                [$key, 'each', 'rule' => ['filter', 'filter'=>'intval']],
            ];
        } else {
            return [
                [$key, 'filter', 'filter' => 'intval'],
            ];
        }
    }

    public function render($model, $attribute, $case)
    {
        // TODO: Implement render() method.
    }
}