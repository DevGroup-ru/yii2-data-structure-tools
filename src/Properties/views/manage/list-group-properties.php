<?php

use DevGroup\DataStructure\Properties\Module;
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\grid\GridView;

/**
 * @var yii\web\View                                $this
 * @var yii\data\ActiveDataProvider                 $dataProvider
 * @var DevGroup\DataStructure\models\PropertyGroup $propertyGroup
 * @var DevGroup\DataStructure\models\Property      $property
 * @var string                                      $editPropertyGroupActionId
 * @var string                                      $listPropertyGroupsActionId
 * @var string                                      $editPropertyActionId
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
                'class' => \DevGroup\AdminUtils\columns\ActionColumn::className(),
                'buttons' => [
                    'edit' => [
                        'url' => 'edit-property',
                        'icon' => 'pencil',
                        'class' => 'btn-primary',
                        'label' => Module::t('app', 'Edit'),
                    ],
                    'delete' => [
                        'url' => 'delete-property',
                        'icon' => 'trash-o',
                        'class' => 'btn-danger',
                        'label' => Module::t('app', 'Delete'),
                        'options' => [
                            'data-action' => 'delete',
                        ],

                    ],
                ],
                'appendUrlParams' => [
                    'propertyGroupId' => $propertyGroup->id,
                ],
            ],
        ],
    ]); ?>

    <p>
        <?=
        Html::a(
            Icon::show('plus') . '&nbsp;' .
            Module::t('app', 'Create property'),
            [
                $editPropertyActionId,
                'propertyGroupId' => $propertyGroup->id,
            ],
            ['class' => 'btn btn-success']
        )
        ?>
    </p>

</div>
