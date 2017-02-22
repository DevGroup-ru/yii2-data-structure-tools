<?php
use DevGroup\AdminUtils\events\ModelEditForm;
use DevGroup\AdminUtils\FrontendHelper;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\actions\EditStaticValue;
use DevGroup\DataStructure\Properties\Module;
use unclead\widgets\MultipleInput;
use unclead\widgets\MultipleInputColumn;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/***
 * @var StaticValue $model
 * @var bool $canSave
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
        <?php if (class_exists('DevGroup\MediaStorage\widgets\MediaInput')): ?>
        <?= DevGroup\MediaStorage\widgets\MediaInput::widget([
            'name' => Html::getInputName($model, 'params[img]'),
            'value' => empty($model->params['img']) === false ? $model->params['img'] : '',
            'controller' => 'media/elfinder',
            'template' => '<div class="input-group">{input}<span class="input-group-btn">{button}</span></div>',
            'options' => ['class' => 'form-control'],
            'buttonOptions' => ['class' => 'btn btn-default'],
            'buttonName' => '<i class="fa fa-plus"></i> ' . Yii::t('app', 'Open gallery'),
            'multiple' => false,
        ]) ?>
        <?php endif;?>

        <?= $form->field($model, 'params')
            ->label(false)
            ->widget(
                MultipleInput::class,
                [
                    'columns' => [
                        [
                            'name' => 'aliases',
                            'type' => MultipleInputColumn::TYPE_TEXT_INPUT,
                            'title' => Module::t('app', 'Aliases')
                        ]
                    ],
                    'data' => empty($model->params['aliases']) === false ? $model->params['aliases'] : []
                ]
            ) ?>

        <?php
        $event = new ModelEditForm($form, $model);
        $this->trigger(EditStaticValue::EVENT_FORM_BEFORE_SUBMIT, $event);
        ?>

        <?php if (true === $canSave) : ?>
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
        <?php endif; ?>

        <?php
        $event = new ModelEditForm($form, $model);
        $this->trigger(EditStaticValue::EVENT_FORM_AFTER_SUBMIT, $event);
        ?>

        <?php ActiveForm::end(); ?>

        <?php
        $event = new ModelEditForm($form, $model);
        $this->trigger(EditStaticValue::EVENT_AFTER_FORM, $event);
        ?>

    </div>


</div>
