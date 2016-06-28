<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;

$dataQuery = $property->getStaticValues();

$options = [];
if ($property->allow_multiple_values) {
    $options['multiple'] = 'multiple';
}
$field = $form->field($model, $property->key)->label($property->name);
echo $field->dropDownList(ArrayHelper::map($dataQuery->all(), 'id', 'name'), $options);
