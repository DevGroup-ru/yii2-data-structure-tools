<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;

class TextField extends AbstractPropertyHandler
{

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
}