<?php

namespace DevGroup\DataStructure\propertyHandler;

use DevGroup\DataStructure\models\Property;
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

    public function render($model, $property, $case)
    {
        // @todo rewrite it. it's a dirty code for test.
        $id = Html::getInputId($model, $property->key);
        return Html::label(isset($model->propertiesAttributes[$property->id]) ? $model->propertiesAttributes[$property->id] : $property->name, $id)
            . Html::input(
            'text',
            Html::getInputName($model, $property->key),
            isset($model->propertiesValues[$property->id]) ? $model->propertiesValues[$property->id] : '',
            [
                'id' => $id,
                'class' => 'form-control',
            ]
        ) . '<br />';
    }
}