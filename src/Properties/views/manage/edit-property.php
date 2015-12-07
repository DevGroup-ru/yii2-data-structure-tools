<?php

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\helpers\FrontendPropertiesHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View                                $this
 * @var DevGroup\DataStructure\models\PropertyGroup $propertyGroup
 * @var DevGroup\DataStructure\models\Property      $model
 * @var integer                                     $applicablePropertyModelId
 * @var string                                      $listPropertyGroupsActionId
 * @var string                                      $listGroupPropertiesActionId
 */

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Property groups'), 'url' => [$listPropertyGroupsActionId]];
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Group properties') . ": $propertyGroup->internal_name", 'url' => [$listGroupPropertiesActionId, 'id'=>$propertyGroup->id]];
if ($model->isNewRecord) {
    $this->title = Yii::t('app', 'New property');
} else {
    $this->title = Yii::t('app', 'Edit property') . ' ' . $model->id;
}

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="manage-properties__property-group-edit">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="property-group-form">



        <?= $form->field($model, 'key') ?>
        <?= $form->field($model, 'is_internal')->checkbox() ?>
        <?= $form->field($model, 'allow_multiple_values')->checkbox() ?>
        <?= $form->field($model, 'data_type')->dropDownList(FrontendPropertiesHelper::dataTypeSelectOptions()) ?>
        <?= $form->field($model, 'property_handler_id')->dropDownList(FrontendPropertiesHelper::handlersSelectOptions()) ?>
        <?= $form->field($model, 'storage_id')->dropDownList(FrontendPropertiesHelper::storagesSelectOptions()) ?>

        <?=
        DevGroup\Multilingual\widgets\MultilingualFormTabs::widget([
            'model' => $model,
            'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_property-multilingual.php',
            'form' => $form,
        ])
        ?>

        <?php
        $event = new ModelEditForm($form, $model);
        $this->trigger(EditProperty::EVENT_FORM_BEFORE_SUBMIT, $event);
        ?>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

    </div>

    <?php
    $event = new ModelEditForm($form, $model);
    $this->trigger(EditProperty::EVENT_FORM_AFTER_SUBMIT, $event);
    ?>

    <?php ActiveForm::end(); ?>


</div>
