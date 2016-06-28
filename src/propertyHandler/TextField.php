<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\validators\ValuesValidator;
use yii\jui\JuiAsset;

/**
 * Class TextField
 *
 * @package DevGroup\DataStructure\propertyHandler
 */
class TextField extends AbstractPropertyHandler
{
    public static $multipleMode = Property::MODE_ALLOW_ALL;

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

    /**
     * @inheritdoc
     */
    public function renderProperty($model, $property, $view, $form = null)
    {
        if ($property->allow_multiple_values === true) {
            JuiAsset::register($this->getView());
            TextFieldAsset::register($this->getView());
        }
        return parent::renderProperty($model, $property, $view, $form);
    }
}
