<?php
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

/***
 * @var StaticValue $staticValue
 * @var ActiveDataProvider $dataProvider
 * @var Property $property
 */

?>
    <h2><?= Html::encode(Yii::t('app', 'Static values')) ?></h2>

    <div class="form-group">
        <?= Html::a(
            Yii::t('app', 'New Static value'),
            [
                'edit-static-value',
                'property_id' => $property->id,
                'return_url' => Yii::$app->request->url
            ],
            [
                'class' => 'btn btn-primary'
            ]
        ) ?>
    </div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $staticValue,
    'columns' => [
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
                    'label' => Yii::t('app', 'Edit'),
                ],
                'delete' => [
                    'url' => 'delete-static-value',
                    'icon' => 'trash-o',
                    'class' => 'btn-danger',
                    'label' => Yii::t('app', 'Delete'),
                    'options' => [
                        'data-action' => 'delete',
                    ],

                ],
            ],
            'appendUrlParams' => [
                'property_id' => $property->id,
                'return_url' => Yii::$app->request->url
            ],
        ],
    ],
    'tableOptions' => ['class' => 'table table-striped table-bordered']
]); ?>