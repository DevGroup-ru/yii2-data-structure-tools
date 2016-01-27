<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;
use yii\jui\JuiAsset;

class TextField extends AbstractPropertyHandler
{
    /**
     * @inheritdoc
     */
    public function getValidationRules(Property $property)
    {
        $key = $property->key;

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

    /**
     * @inheritdoc
     */
    public function renderProperty($model, $property, $view)
    {
        if ($property->allow_multiple_values === true) {
            JuiAsset::register($this->getView());
            TextFieldAsset::register($this->getView());
        }
        return parent::renderProperty($model, $property, $view);
    }
}