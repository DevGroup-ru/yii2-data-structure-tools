<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var \DevGroup\DataStructure\models\Property $property
 * @var \yii\widgets\ActiveForm $form
 * @var Measure $measure
 * @var Measure $measureFrontend
 * @var \yii\web\View $this
 * @var $template string
 * @var $mask string
 */
use DevGroup\Measure\helpers\MeasureHelper;
use DevGroup\Measure\models\Measure;
use yii\widgets\MaskedInput;

$unit = '';
if (empty($measure) === false) {
    $unit = MeasureHelper::t($measure->unit);
}

echo $form->field(
    $model,
    $property->key,
    [
        'template' => "{label}\n <div class=\"input-group\">{input}<div class=\"input-group-addon\">" . $unit . "</div></div>\n{hint}\n{error}"
    ]
)->widget(
    MaskedInput::class,
    [
        'mask' => $mask
    ]
)->hint($template)
    ->label($property->name);
