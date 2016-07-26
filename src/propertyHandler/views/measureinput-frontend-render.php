<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var ActiveForm $form
 * @var Measure $measure
 * @var \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler | \yii\web\View $this
 */


use DevGroup\Measure\helpers\MeasureHelper;
use DevGroup\Measure\models\Measure;
use yii\bootstrap\ActiveForm;
use yii\helpers\Html;


$values = [];

foreach ((array)$model->{$property->key} as $value) {
    $values[] = MeasureHelper::format($value, $measure, $measure);
};
if (count($values) === 0) {
    $values = [''];
}
echo Html::ul($values);
