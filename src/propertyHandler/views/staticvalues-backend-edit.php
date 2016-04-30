<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var ActiveForm $form
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use DevGroup\DataStructure\assets\Select2SortableBundle;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;

$dataQuery = $property->getStaticValues();

if ((int)$property->allow_multiple_values === 1 && !empty($model->{$property->key})) {
    $dataQuery->orderBy([
        new \yii\db\Expression('FIELD(id, ' . implode(',', $model->{$property->key}) . '), sort_order')
    ]);
}

echo Html::tag(
    'div',
    $form->field($model, $property->key)
    ->widget(
        Select2::className(),
        [
            'data' => ArrayHelper::map($dataQuery->all(), 'id', 'name'),
            'options' => [
                'placeholder' => 'Select a value ...',
            ],
            'pluginOptions' => [
                'allowClear' => true,
                'multiple' => (int)$property->allow_multiple_values === 1
            ],
        ]
    ),
    [
        'style' => 'overflow: auto;'
    ]
);

if ((int)$property->allow_multiple_values === 1) {
    Select2SortableBundle::register($this);
    $id = \yii\helpers\Html::getInputId($model, $property->key);
    $this->registerJs(<<<js
    $('#$id').select2Sortable({'bindOrder':"sortableStop"});
js
    );

}
