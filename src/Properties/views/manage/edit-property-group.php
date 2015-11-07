<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/**
 * @var yii\web\View                                $this
 * @var DevGroup\DataStructure\models\PropertyGroup $model
 * @var integer                                     $applicablePropertyModelId
 * @var string                                      $listPropertyGroupsActionId
 */

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Property groups'), 'url' => [$listPropertyGroupsActionId]];
if ($model->isNewRecord) {
    $this->title = Yii::t('app', 'New property group');
} else {
    $this->title = Yii::t('app', 'Edit property group') . ' ' . $model->id;
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

        <?=
        DevGroup\Multilingual\widgets\MultilingualFormTabs::widget([
            'model' => $model,
            'childView' => __DIR__ . DIRECTORY_SEPARATOR . '_property-group-multilingual.php',
            'form' => $form,
        ])
        ?>

        <div class="form-group">
            <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Save'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>


</div>
