<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;

/**
 * Class TextArea
 *
 * @package DevGroup\DataStructure\propertyHandler
 */
class TextArea extends AbstractPropertyHandler
{
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
