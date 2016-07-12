<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;
use DevGroup\DataStructure\propertyStorage\EAV;
use DevGroup\DataStructure\propertyStorage\TableInheritance;

/**
 * Class TextArea
 *
 * @package DevGroup\DataStructure\propertyHandler
 */
class TextArea extends AbstractPropertyHandler
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
        Property::DATA_TYPE_STRING,
        Property::DATA_TYPE_TEXT,
        Property::DATA_TYPE_INVARIANT_STRING,
    ];

    /**
     * @inheritdoc
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
            return [
                [$key, $rule],
            ];
        }
    }
}
