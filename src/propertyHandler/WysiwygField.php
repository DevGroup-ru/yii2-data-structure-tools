<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\TableInheritance;

class WysiwygField extends AbstractPropertyHandler
{

    /** @inheritdoc */
    public static $multipleMode = Property::MODE_ALLOW_ALL;

    /** @inheritdoc */
    public static $allowInSearch = true;

    /** @inheritdoc */
    public static $allowedStorage = [
        EAV::class,
        TableInheritance::class,
    ];

    /** @inheritdoc */
    public static $allowedTypes = [
        Property::DATA_TYPE_TEXT,
        Property::DATA_TYPE_INVARIANT_STRING,
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
            if ($property->allow_multiple_values) {
                return [
                    [$key, 'each', 'rule' => ['safe']],
                ];
            } else {
                return [
                    [$key, 'safe'],
                ];
            }
        }
    }
}