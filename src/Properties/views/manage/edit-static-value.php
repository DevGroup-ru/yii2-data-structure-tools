<?php
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
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
    $this->title = Module::t('app', 'New Static value');
} else {
    $this->title = Module::t('app', 'Edit Static value') . ' ' . $model->id;
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
            <?= Html::submitButton(
                $model->isNewRecord ? Module::t(
                    'app',
                    'Create'
                ) : Module::t(
                    'app',
                    'Save'
                ),
                ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
            ) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>


</div>
