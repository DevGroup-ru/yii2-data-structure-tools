<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\ActiveForm;

$handlerParam = ArrayHelper::getValue($property->params, Property::PACKED_HANDLER_PARAMS);
$className = Json::encode($handlerParam['className']);
$attribute = $handlerParam['nameAttribute'];
$searchAttributes = $handlerParam['attributes'];
$sortOrder = $handlerParam['sortOrder'];
$order = boolval($handlerParam['order']) ? SORT_DESC : SORT_ASC;
$primaries = $model->primaryKey();
$primary = reset($primaries);

$initialData = ArrayHelper::map(
    PropertiesHelper::getRelatedEntitiesByProperty($property, $model),
    $primary,
    $attribute
);
$searchAttributes = Json::encode($searchAttributes);
$url = Url::to(['/properties/manage/ajax-related-entities']);
$multiple = $property->allow_multiple_values;
$options = [
    'multiple' => $multiple,
    'allowClear' => true,
    'minimumInputLength' => 1,
    'ajax' => [
        'url' => $url,
        'dataType' => 'json',
        'data' => new JsExpression(
        /** @lang JavaScript */
            "function (params) {
    return {
        search       : params.term,
        className    : {$className},
        attributes   : {$searchAttributes},
        attribute    : '{$attribute}',
        primary      : '{$primary}',
        sortAttribute: '{$sortOrder}',
        order        : '{$order}'
    };
}"
        ),
        'results' => new JsExpression('function(data,page) { return {results:data.results}; }'),
        'cache' => false,
    ],
];

echo $form->field($model, $property->key)->widget(
    Select2::className(),
    [
        'language' => Yii::$app->language,
        'data' => $initialData,
        'options' => [
            'placeholder' => Yii::t('app', 'Type for search ...'),
            'multiple' => $multiple,
        ],
        'pluginOptions' => $options,
    ]
);
