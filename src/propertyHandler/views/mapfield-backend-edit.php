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

try {
    $data = Json::decode($model->{$property->key});
} catch (Exception $e) {
    $data = null;
}


echo (new ActiveForm())
    ->field($model, $property->key)
    ->widget(
        MapInputWidget::className(),
        [
            'key' => $widget->key,
            'latitude' => $widget->latitude,
            'longitude' => $widget->longitude,
            'zoom' => (!empty($data['zoom']))  ? (int) $data['zoom'] : $widget->zoom,
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
'makePoint',
function(event) {
   event.pointString = event.pointString.replace(/%zoom%/g,  event.MapInputWidget.getMap().getZoom() );
});

$('.kolyunya-map-input-widget').on(
'initializeAfter',
function(event) {

  event.MapInputWidget.getMap().addListener(
    'zoom_changed',
    function(){
          event.MapInputWidget.setPosition(null)
        }
    );
}
);
JAVASCRIPT;
echo $this->registerJs($js);