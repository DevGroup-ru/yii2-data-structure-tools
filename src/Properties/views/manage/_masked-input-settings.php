<?php
/**
 * @var $property Property
 * @var $form ActiveForm
 */
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\propertyHandler\RelatedEntity;
use devgroup\jsoneditor\Jsoneditor;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

echo Yii::t('app', 'Masked input settings');

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][alias]')->label(
    Yii::t('app', 'Class name')
)->dropDownList(RelatedEntity::$aliases, ['prompt' => Yii::t('app', 'Not selected'),]);

echo $form->field($property, 'params[' . Property::PACKED_HANDLER_PARAMS . '][mask]')->label(Yii::t('app', 'Mask'));

?>
<div class="col-sm-12 blog-sidebar">
    <div class="sidebar-module sidebar-module-inset">
        <h4>
            <i class="fa fa-question-circle"></i>
            <?= Yii::t('app', 'Hint') ?>
        </h4>
        <p>
            <?= Yii::t('app', 'You should enter correct settings') ?>
        </p>
        <p>
            <?= Yii::t('app', 'More') . ' ' . Html::a(
                Yii::t('app', 'here'),
                'http://www.yiiframework.com/doc-2.0/yii-widgets-maskedinput.html'
            ) . ' ' . Yii::t('app', 'and') . ' ' . Html::a(
                Yii::t('app', 'here'),
                'http://demos.krajee.com/masked-input'
            ) ?>
        </p>
    </div>
</div>
