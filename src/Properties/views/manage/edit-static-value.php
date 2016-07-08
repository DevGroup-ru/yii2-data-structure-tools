<?php
use DevGroup\AdminUtils\FrontendHelper;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/***
 * @var StaticValue $model
 */

$this->params['breadcrumbs'][] = [
    'label' => Module::t('app', 'Edit property') . ' ' . $model->property->id,
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

        <?= FrontendHelper::formSaveButtons(
            $model,
            Url::to(
                [
                    'edit-property',
                    'id' => $model->property_id,
                    'propertyGroupId' => $model->property->defaultPropertyGroup->id
                ]
            )
        ) ?>

        <?php ActiveForm::end(); ?>

    </div>


</div>
