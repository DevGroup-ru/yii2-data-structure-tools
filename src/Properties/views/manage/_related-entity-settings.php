<?php
/**
 * @var $property Property
 * @var $form ActiveForm
 */
use app\models\Product;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

echo Yii::t('app', 'Related entity settings');

// @note now this is hardcoded, but later will be rewrited to some model or module
$classNames = [
    Product::class => 'product',
];
$params = $property->params;
$className = ArrayHelper::getValue($params, Property::PACKED_HANDLER_PARAMS . '.className', '');
$attrs = PropertiesHelper::getAttributeNamesByClassName($className);
$opts = ['disabled' => false, 'required' => true];
if (count($attrs) === 0) {
    $opts['disabled'] = true;
}
echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][className]')->label(
    Yii::t('app', 'Class name')
)->dropDownList(
    $classNames,
    ArrayHelper::merge(
        $opts,
        [
            'disabled' => false,
            'prompt' => Yii::t('app', 'Not selected'),
            'onchange' => new JsExpression('RelatedProperty.classNameSelected(this.value)'),
        ]
    )
);

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][nameAttribute]')->label(
    Yii::t('app', 'Attribute name')
)->dropDownList(
    $attrs,
    ArrayHelper::merge(
        $opts,
        ['prompt' => Yii::t('app', 'Not selected'),]
    )

);

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][attributes]')->label(
    Yii::t('app', 'Attributes')
)->dropDownList(
    $attrs,
    ArrayHelper::merge(
        $opts,
        ['multiple' => true,]
    )
);

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][sortOrder]')->label(
    Yii::t('app', 'Sort order')
)->dropDownList(
    $attrs,
    ArrayHelper::merge(
        $opts,
        ['prompt' => Yii::t('app', 'Not selected'),]
    )
);

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][order]')->checkbox(
    ['label' => Yii::t('app', 'Order desc')]
);
