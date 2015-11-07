<?php

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

    <div class="property-group-form">

        <?php $form = ActiveForm::begin(); ?>

        <?= $form->field($model, 'is_internal')->checkbox() ?>
        <?= $form->field($model, 'allow_multiple_values')->checkbox() ?>

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
