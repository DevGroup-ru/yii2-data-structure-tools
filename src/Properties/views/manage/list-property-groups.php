<?php

use DevGroup\AdminUtils\Helper;
use DevGroup\DataStructure\Properties\Module;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var array $applicablePropertyModels
 * @var array $currentApplicablePropertyModel
 * @var DevGroup\DataStructure\models\PropertyGroup $model
 * @var string $editPropertyGroupActionId
 */

$this->title = Module::t('app', 'Property groups');
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
                'attribute' => 'is_deleted',
                'label' => Module::t('app', 'Show deleted?'),
                'value' => function ($model) {
                    return $model->isDeleted() === true ? Module::t('app', 'Deleted') : Module::t('app', 'Active');
                },
                'filter' => [
                    Module::t('app', 'Show only active'),
                    Module::t('app', 'Show only deleted')
                ],
                'filterInputOptions' => [
                    'class' => 'form-control',
                    'id' => null,
                    'prompt' => Module::t('app', 'Show all')
                ]
            ],
            [
                'class' => \DevGroup\AdminUtils\columns\ActionColumn::className(),
                'buttons' => function ($model, $key, $index, $column) use ($currentApplicablePropertyModel) {
                    $result = [
                        'edit' => [
                            'url' => 'edit-property-group',
                            'icon' => 'pencil',
                            'class' => 'btn-primary',
                            'label' => Module::t('app', 'Edit'),
                        ],
                        'properties' => [
                            'url' => 'list-group-properties',
                            'icon' => 'list',
                            'class' => 'btn-success',
                            'label' => Module::t('app', 'Group properties'),
                            'text' => Module::t('app', 'Properties'),
                        ],
                    ];

                    if ($model->isDeleted() === false) {
                        $result['delete'] = [
                            'url' => 'delete-property-group',
                            'icon' => 'trash-o',
                            'class' => 'btn-warning',
                            'label' => Module::t('app', 'Delete'),
                            'options' => [
                                'data-action' => 'delete',
                            ],
                        ];
                    } else {
                        $result['restore'] = [
                            'url' => 'restore-property-group',
                            'icon' => 'undo',
                            'class' => 'btn-info',
                            'label' => Module::t('app', 'Restore'),
                            'urlAppend' => [
                                'returnUrl' => Helper::returnUrl()
                            ]
                        ];
                        $result['delete'] = [
                            'url' => 'delete-property-group',
                            'urlAppend' => [
                                'hard' => 1,
                                'applicablePropertyModelId' => $currentApplicablePropertyModel['id'],
                            ],
                            'icon' => 'trash-o',
                            'class' => 'btn-danger',
                            'label' => Module::t('app', 'Delete'),
                            'options' => [
                                'data-action' => 'delete',
                            ],
                        ];
                    }

                    return $result;
                },
                'appendUrlParams' => [
                    'applicablePropertyModelId' => $currentApplicablePropertyModel['id'],
                ],
            ],
        ],
    ]); ?>

    <?php if (true === Yii::$app->user->can('dst-property-group-edit')) : ?>
        <p>
            <?= Html::a(
                Icon::show('plus') . '&nbsp;' .
                Module::t('app', 'Create property group'),
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
            ) ?>
        </p>
    <?php endif; ?>

</div>
