<?php

use kartik\icons\Icon;
use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View                                $this
 * @var yii\data\ActiveDataProvider                 $dataProvider
 * @var array                                       $applicablePropertyModels
 * @var array                                       $currentApplicablePropertyModel
 * @var DevGroup\DataStructure\models\PropertyGroup $model
 * @var string                                      $editPropertyGroupActionId
 */

$this->title = Yii::t('app', 'Property groups');
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="manage-controller__list-property-groups">

    <h1><?= Html::encode($this->title) ?></h1>
    <?=
    \yii\bootstrap\Nav::widget([
        'items' => array_map(
            function ($item) use ($currentApplicablePropertyModel) {
                return [
                        'label' => $item['name'],
                        'url' => [Yii::$app->requestedAction->id, 'applicablePropertyModelId' => $item['id']],
                        'active' => $currentApplicablePropertyModel['id'] === $item['id'],
                ];
            },
            $applicablePropertyModels
        ),
        'options' => [
            'class' => 'nav-tabs',
        ],
    ]) ?>



    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            'id',
            'internal_name',
            'name', // there's no need to specify property_group_translation prefix
            [
                'class' => \DevGroup\AdminUtils\columns\ActionColumn::className(),
                'buttons' => [
                    'edit' => [
                        'url' => 'edit-property-group',
                        'icon' => 'pencil',
                        'class' => 'btn-primary',
                        'label' => Yii::t('app', 'Edit'),
                    ],
                    'properties' => [
                        'url' => 'list-group-properties',
                        'icon' => 'list',
                        'class' => 'btn-success',
                        'label' => Yii::t('app', 'Group properties'),
                        'text' => Yii::t('app', 'Properties'),
                    ],
                    'delete' => [
                        'url' => 'delete-property-group',
                        'icon' => 'trash-o',
                        'class' => 'btn-danger',
                        'label' => Yii::t('app', 'Delete'),
                        'options' => [
                            'data-action' => 'delete',
                        ],

                    ],
                ],
                'appendUrlParams' => [
                    'applicablePropertyModelId' => $currentApplicablePropertyModel['id'],
                ],
            ],
        ],
    ]); ?>

    <p>
        <?=
        Html::a(
            Icon::show('plus') . '&nbsp;' .
            Yii::t('app', 'Create property group'),
            [
                $editPropertyGroupActionId,
                'applicablePropertyModelId' => $currentApplicablePropertyModel['id'],
            ],
            [
                'class' => 'btn btn-success',
                'data-admin-url' => \yii\helpers\Url::to([
                    $editPropertyGroupActionId,
                    'applicablePropertyModelId' => $currentApplicablePropertyModel['id'],
                ])

            ]
        )
        ?>
    </p>

</div>
