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

$field = $form->field($model, $property->key)->label($property->name);
if ((int) $property->allow_multiple_values === 1) {
    echo $field->checkboxList(ArrayHelper::map($dataQuery->all(), 'id', 'name'));
} else {
    echo $field->radioList(ArrayHelper::map($dataQuery->all(), 'id', 'name'));
}
