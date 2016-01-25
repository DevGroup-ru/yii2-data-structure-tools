<?php

/**
 * @var $tabs array
 * @var $this \yii\web\View
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
?>
<script>
jQuery(function() {
    jQuery('body').on('click', '[data-action="add-property-group"]', function() {
        var $this = jQuery(this);
        var url = $this.parents('[data-add-url]').eq(0).data('add-url');
        jQuery.ajax({
            'url': url,
            'data': {
                'groupId': $this.data('group-id')
            },
            'success': function(data) {
                location.reload();
            }
        });
        return false;
    }).on('click', '[data-action="delete-property-group"]', function() {
        var $this = jQuery(this);
        var url = $this.parents('[data-delete-url]').eq(0).data('delete-url');
        jQuery.ajax({
            'url': url,
            'data': {
                'groupId': $this.data('group-id')
            },
            'success': function(data) {
                location.reload();
            }
        });
        return false;
    });
});
</script>