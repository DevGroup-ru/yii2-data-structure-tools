<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\StaticValues as SVStorage;
use DevGroup\DataStructure\propertyStorage\TableInheritance;

class HiddenField extends AbstractPropertyHandler
{
    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_STRING,
        Property::DATA_TYPE_INTEGER,
        Property::DATA_TYPE_FLOAT,
        Property::DATA_TYPE_TEXT,
        Property::DATA_TYPE_PACKED_JSON,
        Property::DATA_TYPE_BOOLEAN,
        Property::DATA_TYPE_INVARIANT_STRING,
    ];

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class,
        TableInheritance::class,
        SVStorage::class,
    ];

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