<?php
/**
 * @var $model Property
 * @var $form ActiveForm
 * @var $measures []
 */

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\Measure\helpers\MeasureHelper;
use yii\widgets\ActiveForm;

echo $form
    ->field($model, 'params[' . Property::PACKED_HANDLER_PARAMS . '][measure_id]')
    ->label(MeasureHelper::t('Unit'))
    ->dropDownList($measures);

echo $form
    ->field($model, 'params[' . Property::PACKED_HANDLER_PARAMS . '][measure_frontend_id]')
    ->label(Module::t('app', 'Unit in frontend'))
    ->dropDownList(
        $measures,
        [
            'prompt' => Module::t('app', 'Default')
        ]
    );


echo $form
    ->field(
        $model,
        'params[' . Property::PACKED_HANDLER_PARAMS . '][template]'
    )
    ->input('string', ['placeholder' => '{height}/{width}/{deep}'])
    ->label(Yii::t('app', 'Template'));
