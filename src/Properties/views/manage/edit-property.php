<?php

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\helpers\FrontendPropertiesHelper;
use DevGroup\DataStructure\Properties\Module;
use \DevGroup\DataStructure\assets\PropertiesAsset;
use DevGroup\Multilingual\widgets\MultilingualFormTabs;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View $this
 * @var DevGroup\DataStructure\models\PropertyGroup $propertyGroup
 * @var DevGroup\DataStructure\models\Property $model
 * @var integer $applicablePropertyModelId
 * @var string $listPropertyGroupsActionId
 * @var string $listGroupPropertiesActionId
 * @var bool $canSave
 */

$this->params['breadcrumbs'][] = [
    'label' => Module::t('app', 'Property groups'),
    'url' => [$listPropertyGroupsActionId]
];
$this->params['breadcrumbs'][] = [
    'label' => Module::t('app', 'Group properties') . ": $propertyGroup->internal_name",
    'url' => [$listGroupPropertiesActionId, 'id' => $propertyGroup->id]
];
if ($model->isNewRecord) {
    $this->title = Module::t('app', 'New property');
} else {
    $this->title = Module::t('app', 'Edit property') . ' ' . $model->id;
}
$required = $model->isRequired();

$this->params['breadcrumbs'][] = $this->title;

PropertiesAsset::register($this);

?>
<div class="manage-properties__property-group-edit">

    <h1><?= Html::encode($this->title) ?></h1>

    <?php $form = ActiveForm::begin(); ?>

    <div class="property-group-form">
        <div class="row">
            <div class="col-xs-12 col-md-8">
                <?=
                $form->field($model, 'property_handler_id')
                    ->dropDownList(
                        FrontendPropertiesHelper::handlersSelectOptions(),
                        [
                            'data-wizard-next' => 'property-data_type',
                            'data-wizard' => 'handler',
                            'prompt' => Module::t('app', 'Select') . ' ' . Module::t('app', 'Handler')
                        ]
                    )
                ?>
                <?= $form->field($model, 'key') ?>
                <?= MultilingualFormTabs::widget([
                    'model' => $model,
                    'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_property-multilingual.php',
                    'form' => $form,
                ]) ?>
            </div>
            <div class="col-xs-12 col-md-4">
                <?= $form->field($model, 'data_type')->dropDownList(FrontendPropertiesHelper::dataTypeSelectOptions(), [
                    'data-wizard-next' => 'property-storage_id',
                    'data-wizard' => 'data-type',
                    'prompt' => Module::t('app', 'Select') . ' ' . Module::t('app', 'Data Type')
                ]) ?>
                <?= $form
                    ->field($model, 'storage_id')
                    ->dropDownList(
                        FrontendPropertiesHelper::storagesSelectOptions(), [
                            'data-wizard' => 'storage',
                            'prompt' => Module::t('app', 'Select') . ' ' . Module::t('app', 'Storage')
                        ]
                    ) ?>
                <?= $form->field($model, 'is_internal')->checkbox() ?>
                <?= $form->field($model, 'in_search')->checkbox(['data-wizard' => 'init']) ?>
                <?= $form->field($model, 'allow_multiple_values')->checkbox(['data-wizard' => 'init']) ?>
                <?= Html::checkbox(
                    Property::PACKED_ADDITIONAL_RULES . '[required]',
                    $required,
                    [
                        'label' => Module::t('app', 'Required'),
                    ]
                ) ?>
            </div>
            <div class="col-xs-12">
                <?php
                $event = new ModelEditForm($form, $model);
                $this->trigger(EditProperty::EVENT_FORM_BEFORE_SUBMIT, $event);
                ?>

                <?php if (true === $canSave) : ?>
                    <div class="form-group">
                        <?= Html::submitButton($model->isNewRecord
                            ? Module::t('app', 'Create')
                            : Module::t('app', 'Save'),
                            ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
                        ) ?>
                    </div>
                <?php endif; ?>

                <?php
                $event = new ModelEditForm($form, $model);
                $this->trigger(EditProperty::EVENT_FORM_AFTER_SUBMIT, $event);
                ?>
            </div>
        </div>

    </div>


    <?php $form::end(); ?>
    <?php
    $event = new ModelEditForm($form, $model);
    $this->trigger(EditProperty::EVENT_AFTER_FORM, $event);
    ?>
</div>
