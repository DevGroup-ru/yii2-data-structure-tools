<?php


namespace DevGroup\DataStructure\propertyHandler;


use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;

class HiddenField extends AbstractPropertyHandler
{

    /**
     * Get validation rules for a property.
     *
     * @param Property $property
     *
     * @return array of ActiveRecord validation rules
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;
        if (true === $property->canTranslate()) {
            return [
                [$key, ValuesValidator::class, 'skipOnEmpty' => true],
            ];
        } else {
            $rule = Property::dataTypeValidator($property->data_type) ?: 'safe';
            if ($property->allow_multiple_values) {
                return [
                    [$key, 'each', 'rule' => [$rule]],
                ];
            } else {
                return [
                    [$key, $rule],
                ];
            }
        }
    }
}