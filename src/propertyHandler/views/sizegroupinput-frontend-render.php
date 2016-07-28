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
use yii\helpers\Html;

if (empty($measureFrontend) === false) {
    $measureFrom = $measure;
    $measureTo = $measureFrontend;
} else {
    $measureFrom = null;
    $measureTo = $measure;
}
echo Html::ul(
    $values,
    [
        'item' => function ($item, $index) use ($measureFrom, $measureTo) {
            return Html::tag(
                'li',
                $index . ': ' . MeasureHelper::format(
                    $item,
                    $measureTo,
                    $measureFrom
                )
            );
        }
    ]
);
