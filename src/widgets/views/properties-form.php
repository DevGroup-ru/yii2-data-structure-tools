<?php

/**
 * @var $availableGroups array
 * @var $tabs array
 * @var $this \yii\web\View | \DevGroup\DataStructure\widgets\PropertiesForm
 */

use yii\helpers\Url;

?>
<?=
\yii\bootstrap\Tabs::widget(
    [
        'encodeLabels' => false,
        'items' => $tabs,
        'options' => [
            'data-add-url' => Url::toRoute(
                [
                    $this->context->addPropertyGroupRoute,
                    'className' => get_class($this->context->model),
                    'modelId' => $this->context->model->id,
                ]
            ),
            'data-delete-url' => Url::toRoute(
                [
                    $this->context->deletePropertyGroupRoute,
                    'className' => get_class($this->context->model),
                    'modelId' => $this->context->model->id,
                ]
            ),
        ],
    ]
);
