<?php

use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\DataStructure\Properties\actions\EditProperty;
use DevGroup\DataStructure\Properties\helpers\FrontendPropertiesHelper;
use DevGroup\DataStructure\Properties\Module;
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

$this->params['breadcrumbs'][] = ['label' => Module::t('app', 'Property groups'), 'url' => [$listPropertyGroupsActionId]];
$this->params['breadcrumbs'][] = ['label' => Module::t('app', 'Group properties') . ": $propertyGroup->internal_name", 'url' => [$listGroupPropertiesActionId, 'id'=>$propertyGroup->id]];
if ($model->isNewRecord) {
    $this->title = Module::t('app', 'New property');
} else {
    $this->title = Module::t('app', 'Edit property') . ' ' . $model->id;
}

$this->params['breadcrumbs'][] = $this->title;

$js = <<<JS
DataStructureTools = {
    'init': function() {
        // constants definition?
        this.MODE_ALLOW_NOTHING = 0;
        this.MODE_ALLOW_SINGLE = 0b00000001;
        this.MODE_ALLOW_MULTIPLE = 0b00000010;
        this.MODE_ALLOW_ALL = 0b00000011;
        // get elements and attach events
        this.multipleCheckbox = jQuery('#property-allow_multiple_values');
        this.handlersSelectOptions = jQuery('#property-property_handler_id').change(function() {
            DataStructureTools.updateEditForm();
        }).find('option');
        this.storageSelect = jQuery('#property-storage_id').change(function() {
            DataStructureTools.updateEditForm();
        });
        this.storageSelectOptions = this.storageSelect.find('option');
        this.updateEditForm();
    },
    'updateEditForm': function() {
        this.currentType = this.handlersSelectOptions.filter(':selected').data('type');
        this.currentMode = this.multipleCheckbox.is(':checked') ? this.MODE_ALLOW_MULTIPLE : this.MODE_ALLOW_SINGLE;
        // allow multiple
        var handlerMode = this.handlersSelectOptions.filter(':checked').data('mode');
        if ((handlerMode & 3) != 3) {
            this.multipleCheckbox.attr('disabled', 'disabled');
        } else {
            this.multipleCheckbox.removeAttr('disabled');
        }
        if ((handlerMode & this.currentMode) != this.currentMode) {
            this.multipleCheckbox.prop('checked', !this.multipleCheckbox.prop('checked'));
            this.currentMode = this.multipleCheckbox.is(':checked') ? this.MODE_ALLOW_MULTIPLE : this.MODE_ALLOW_SINGLE;
        }
        // storages
        this.storageSelectOptions.each(function() {
            var option = jQuery(this);
            if (
                option.data('type') == DataStructureTools.currentType
                && (option.data('mode') & DataStructureTools.currentMode) == DataStructureTools.currentMode
            ) {
                option.removeAttr('disabled');
            } else {
                option.attr('disabled', 'disabled');
            }
        });
        if (this.storageSelectOptions.filter(':selected').attr('disabled')) {
            this.storageSelect.val(this.storageSelectOptions.not('[disabled=disabled]:first').attr('value'));
        }
    }
};
JS;
$this->registerJs($js, \yii\web\View::POS_END);
$this->registerJs("DataStructureTools.init();");

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
                            'options' => FrontendPropertiesHelper::handlersSelectOptionOptions(),
                        ]
                    )
                ?>
                <?= $form->field($model, 'key') ?>
                <?=
                DevGroup\Multilingual\widgets\MultilingualFormTabs::widget([
                    'model' => $model,
                    'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_property-multilingual.php',
                    'form' => $form,
                ])
                ?>
            </div>
            <div class="col-xs-12 col-md-4">
                <?= $form->field($model, 'data_type')->dropDownList(FrontendPropertiesHelper::dataTypeSelectOptions()) ?>
                <?=
                $form
                    ->field($model, 'storage_id')
                    ->dropDownList(
                        FrontendPropertiesHelper::storagesSelectOptions(),
                        [
                            'options' => FrontendPropertiesHelper::storagesSelectOptionOptions(),
                        ]
                    )
                ?>
                <?= $form->field($model, 'is_internal')->checkbox() ?>
                <?= $form->field($model, 'in_search')->checkbox() ?>
                <?= $form->field($model, 'allow_multiple_values')->checkbox() ?>
            </div>
            <div class="col-xs-12">
                <?php
                $event = new ModelEditForm($form, $model);
                $this->trigger(EditProperty::EVENT_FORM_BEFORE_SUBMIT, $event);
                ?>

                <?php
                $event = new ModelEditForm($form, $model);
                $this->trigger(EditProperty::EVENT_FORM_AFTER_SUBMIT, $event);
                ?>

                <?php
                $event = new ModelEditForm($form, $model);
                $this->trigger(EditProperty::EVENT_AFTER_FORM, $event);
                ?>

                <div class="form-group">
                    <?= Html::submitButton($model->isNewRecord ? Module::t('app', 'Create') : Module::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
                </div>
            </div>
        </div>

    </div>


    <?php $form::end(); ?>

</div>
