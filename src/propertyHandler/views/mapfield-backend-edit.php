<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var DevGroup\DataStructure\propertyHandler\MapField $widget
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use kolyunya\yii2\widgets\MapInputWidget;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

echo (new ActiveForm())
    ->field($model, $property->key)
    ->widget(
        MapInputWidget::className(),
        [
            'key' => $widget->key,
            'latitude' => $widget->latitude,
            'longitude' => $widget->longitude,
            'zoom' => $widget->zoom,
            'width' => $widget->width,
            'height' => $widget->height,
            'pattern' => $widget->pattern,
            'mapType' => $widget->mapType,
            'animateMarker' => $widget->animateMarker,
            'alignMapCenter' => $widget->alignMapCenter,
            'enableSearchBar' => $widget->enableSearchBar,
        ]
    );
$js = <<<JAVASCRIPT

$('.kolyunya-map-input-widget').on(
'initialize:after',
function(event, obj) {
    obj.MapInputWidget.setZoom(12);
    obj.MapInputWidget.getMap().addListener(
    'zoom_changed',
    function(){
    
        }
    );
})


JAVASCRIPT;
echo $this->registerJs($js);