<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

echo (new ActiveForm())
    ->field($model, $property->key)
    ->widget(
        Select2::className(),
        [
            'data' => ArrayHelper::map($property->staticValues, 'id', 'name'),
            'options' => ['placeholder' => 'Select a value ...'],
            'pluginOptions' => [
                'allowClear' => true,
                'multiple' => $property->allow_multiple_values
            ],
        ]
    );
