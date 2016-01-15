<?php
use DevGroup\DataStructure\models\StaticValue;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/***
 * @var StaticValue $model
 */

$this->params['breadcrumbs'][] = [
    'label' => $model->property->name,
    'url' => [
        'edit-property',
        'id' => $model->property_id,
        'propertyGroupId' => $model->property->defaultPropertyGroup->id
    ],
];

if ($model->isNewRecord) {
    $this->title = Yii::t('app', 'New static value');
} else {
    $this->title = Yii::t('app', 'Edit static value') . ' ' . $model->id;
}

$this->params['breadcrumbs'][] = $this->title;
?>

<div class="manage-properties__property-group-edit">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="property-group-form">

        <?php $form = ActiveForm::begin(); ?>
        <?= $form->field($model, 'sort_order')->textInput() ?>
        <?=
        DevGroup\Multilingual\widgets\MultilingualFormTabs::widget([
            'model' => $model,
            'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_static-value-multilingual.php',
            'form' => $form,
        ])
        ?>
        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Save'),
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>


</div>
