<?php
/**
 * @var $property Property
 */
use DevGroup\DataStructure\models\Property;
use devgroup\jsoneditor\Jsoneditor;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;

echo Yii::t('app', 'Masked input settings');

echo Jsoneditor::widget(
    [
        'editorOptions' => [
            'modes' => ['code', 'form', 'text', 'tree', 'view'],
            'mode' => 'tree',
        ],
        'name' => Property::PACKED_HANDLER_PARAMS,
        'options' => [],
        'value' => Json::encode(ArrayHelper::getValue($property, 'params.' . Property::PACKED_HANDLER_PARAMS)),
    ]
);

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
