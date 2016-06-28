<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */


use yii\bootstrap\ActiveForm;
use yii\helpers\Html;


$values = (array) $model->{$property->key};
if (count($values) === 0) {
    $values = [''];
}
echo Html::ul($values);
