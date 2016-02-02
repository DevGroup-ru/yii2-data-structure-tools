<?php
use arogachev\sortable\grid\SortableColumn;
use DevGroup\AdminUtils\Helper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\Pjax;

/***
 * @var StaticValue $staticValue
 * @var ActiveDataProvider $dataProvider
 * @var Property $property
 */

?>

<h2><?= Html::encode(Module::t('app', 'Static values')) ?></h2>

<div class="form-group">
    <?= Html::a(
        Module::t('app', 'New Static value'),
        [
            'edit-static-value',
            'property_id' => $property->id,
            'return_url' => Helper::returnUrl()
        ],
        [
            'class' => 'btn btn-primary'
        ]
    ) ?>

    <?php $pjax = Pjax::begin([
        'options'=> [
            'style' =>'overflow: scroll;'
        ]
    ]); ?>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $staticValue,
        'id' => 'static-values-sortable',
        'columns' => [
            [
                'class' => SortableColumn::className(),
                'template' => '<div class="sortable-section">{moveWithDragAndDrop}</div>',
                'confirmMove' => false,
                'baseUrl' => Url::to(['/properties/sort']) . '/',
                'gridContainerId' => $pjax->id,
                'visible' => true
            ],
            'id',
            'name', // there's no need to specify property_translation prefix
            'slug',
            'sort_order',
            [
                'class' => \DevGroup\AdminUtils\columns\ActionColumn::className(),
                'buttons' => [
                    'edit' => [
                        'url' => 'edit-static-value',
                        'icon' => 'pencil',
                        'class' => 'btn-primary',
                        'label' => Module::t('app', 'Edit'),
                        'options' => [
                            'data' => [
                                'pjax' => 'false'
                            ]
                        ]
                    ],
                    'delete' => [
                        'url' => 'delete-static-value',
                        'icon' => 'trash-o',
                        'class' => 'btn-danger',
                        'label' => Module::t('app', 'Delete'),
                        'options' => [
                            'data' => [
                                'pjax' => 'false',
                                'action' => 'delete'
                            ]
                        ],

                    ],
                ],
                'appendUrlParams' => [
                    'property_id' => $property->id,
                    'return_url' => Helper::returnUrl()
                ],
            ],
        ],

         'tableOptions' => ['class' => 'table table-striped table-bordered'],
    ]); ?>
    <div class="clearfix"></div>
    <?php Pjax::end(); ?>
