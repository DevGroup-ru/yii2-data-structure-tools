<?php
/**
 * @var $model Property
 * @var $form ActiveForm
 * @var $measures []
 */

use DevGroup\DataStructure\models\Property;
use DevGroup\Measure\helpers\MeasureHelper;
use yii\widgets\ActiveForm;

echo $form
    ->field($model, 'params[' . Property::PACKED_HANDLER_PARAMS . '][measure_id]')
    ->label(MeasureHelper::t('Unit'))
    ->dropDownList($measures);
