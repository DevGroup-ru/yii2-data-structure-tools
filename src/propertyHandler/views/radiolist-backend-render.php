<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */


use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$valuesToNames = ArrayHelper::map($property->getStaticValues()->all(), 'id', 'name');

$values = array_map(
    function ($val) use ($valuesToNames) {
        return $valuesToNames[$val];
    },
    (array) $model->{$property->key}
);
if (count($values) === 0) {
    $values = [''];
}
echo Html::ul($values);
