<?php

use DevGroup\DataStructure\Properties\Module;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use DevGroup\Multilingual\widgets\MultilingualFormTabs;

/**
 * @var yii\web\View $this
 * @var DevGroup\DataStructure\models\PropertyGroup $model
 * @var integer $applicablePropertyModelId
 * @var string $listPropertyGroupsActionId
 * @var bool $canSave
 */

$this->params['breadcrumbs'][] = ['label' => Module::t('app', 'Property groups'), 'url' => [$listPropertyGroupsActionId]];
if ($model->isNewRecord) {
    $this->title = Module::t('app', 'New property group');
} else {
    $this->title = Module::t('app', 'Edit property group {id}', ['id' => $model->id]);
}
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="manage-properties__property-group-edit">
    <h1><?= Html::encode($this->title) ?></h1>

    <div class="property-group-form">
        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'internal_name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'sort_order')->textInput() ?>

        <?= $form->field($model, 'is_auto_added')->checkbox() ?>

        <?= MultilingualFormTabs::widget([
            'model' => $model,
            'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_property-group-multilingual.php',
            'form' => $form,
        ]) ?>
        <?php if (true === $canSave) : ?>
            <div class="form-group">
                <?= Html::submitButton(
                    $model->isNewRecord
                        ? Module::t('app', 'Create')
                        : Module::t('app', 'Save'),
                    ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']
                ) ?>
            </div>
        <?php endif; ?>
        <?php ActiveForm::end(); ?>
    </div>
</div>
