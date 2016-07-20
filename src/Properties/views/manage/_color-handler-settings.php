<?php
/**
 * @var $model StaticValue
 * @var $form ActiveForm
 */


use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use kartik\color\ColorInput;
use yii\widgets\ActiveForm;

echo $form
    ->field($model, 'params[ColorHandler][hex]')
    ->label(Module::t('app', 'Color'))
    ->widget(ColorInput::class);