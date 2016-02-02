<?php

/**
 * @var $availableGroups array
 * @var $tabs array
 * @var $this \yii\web\View | \DevGroup\DataStructure\widgets\PropertiesForm
 */

use yii\helpers\Url;
use yii\widgets\Pjax;

?>
<?php $widget = \yii\bootstrap\Tabs::begin(
    [
        'encodeLabels' => false,
        'items' => $tabs,
        'options' => [
            'data' => [
                'add-url' => Url::toRoute(
                    [
                        $this->context->addPropertyGroupRoute,
                        'className' => get_class($this->context->model),
                        'modelId' => $this->context->model->id,
                    ]),
                'delete-url' => Url::toRoute(
                    [
                        $this->context->deletePropertyGroupRoute,
                        'className' => get_class($this->context->model),
                        'modelId' => $this->context->model->id,
                    ]),
                'model-id' => $this->context->model->id

            ]

        ]
    ]
);
$id = $widget->id;
$this->registerJs(<<<JAVASCRIPT
    var pg = new propertyGroup('$id');
    jQuery('form[method="post"]')
        .on('change', '[name="plus-group"]', function () {
            pg.addGroup(jQuery(this).val())
        })
        .on('click', '[data-action="delete-property-group"]', function () {
            pg.deleteGroup(jQuery(this).data('group-id'));
        });
JAVASCRIPT
);
$widget->end();

