<?php

use DevGroup\AdminUtils\columns\ActionColumn;
use DevGroup\DataStructure\Properties\Module;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View $this
 * @var yii\data\ActiveDataProvider $dataProvider
 * @var DevGroup\DataStructure\models\PropertyGroup $propertyGroup
 * @var DevGroup\DataStructure\models\Property $property
 * @var string $editPropertyGroupActionId
 * @var string $listPropertyGroupsActionId
 * @var string $editPropertyActionId
 */

$this->title = Module::t('app', 'Group properties') . ": {$propertyGroup->name}";
$this->params['breadcrumbs'][] = [
    'label' => Module::t('app', 'Property groups'),
    'url' => [$listPropertyGroupsActionId],
];
$this->params['breadcrumbs'][] = $this->title;


?>
<div class="manage-controller__list-group-properties">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $model,
        'columns' => [
            'id',
            'name', // there's no need to specify property_translation prefix
            'is_internal',
            'property_handler_id',
            'data_type',
            'allow_multiple_values',
            'storage_id',
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
                'class' => ActionColumn::className(),
                'buttons' => function ($model, $key, $index, $column) use ($propertyGroup) {
                    $result = [
                        'edit' => [
                            'url' => 'edit-property',
                            'icon' => 'pencil',
                            'class' => 'btn-primary',
                            'label' => Module::t('app', 'Edit'),
                        ],
                    ];

                    if ($model->isDeleted() === false) {
                        $result['delete'] = [
                            'url' => 'delete-property',
                            'visible' => false,
                            'icon' => 'trash-o',
                            'class' => 'btn-warning',
                            'label' => Module::t('app', 'Delete'),
                            'options' => [
                                'data-action' => 'delete',
                            ],
                        ];
                    } else {
                        $result['restore'] = [
                            'url' => 'restore-property',
                            'icon' => 'undo',
                            'class' => 'btn-info',
                            'label' => Module::t('app', 'Restore'),
                        ];
                        $result['delete'] = [
                            'url' => 'delete-property',
                            'urlAppend' => ['hard' => 1, 'propertyGroupId' => $propertyGroup->id],
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
                    'propertyGroupId' => $propertyGroup->id,
                ],
            ],
        ],
    ]); ?>

    <?php if (true === Yii::$app->user->can('dst-property-edit')) : ?>
        <p>
            <?= Html::a(
                Icon::show('plus') . '&nbsp;' .
                Module::t('app', 'Create property'),
                [
                    $editPropertyActionId,
                    'propertyGroupId' => $propertyGroup->id,
                ],
                ['class' => 'btn btn-success']
            ) ?>
        </p>
    <?php endif; ?>

</div>
