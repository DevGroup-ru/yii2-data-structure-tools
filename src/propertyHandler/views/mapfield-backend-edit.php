<?php
/**
 * @var \yii\db\ActiveRecord $model
 * @var DevGroup\DataStructure\propertyHandler\MapField $this->context
 * @var \DevGroup\DataStructure\models\Property $property
 * @var yii\web\View $this
 */

use hector68\yii2\widgets\MapInputWidget;
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
            'key' => $this->context->key,
            'latitude' => $this->context->latitude,
            'longitude' => $this->context->longitude,
            'zoom' => (!empty($data['zoom']))  ? (int) $data['zoom'] : $this->context->zoom,
            'description' =>  (!empty($data['description']))  ?  $data['description'] : $this->context->description,
            'width' => $this->context->width,
            'height' => $this->context->height,
            'pattern' => $this->context->pattern,
            'mapType' => $this->context->mapType,
            'animateMarker' => $this->context->animateMarker,
            'alignMapCenter' => $this->context->alignMapCenter,
            'enableSearchBar' => $this->context->enableSearchBar,
        ]
    );
$js = <<<JAVASCRIPT
$('.hector68-map-input-widget').on(
'makePoint',
function(event) {
   event.pointString = event.pointString.replace(/%zoom%/g,  event.MapInputWidget.getMap().getZoom() );
   event.pointString = event.pointString.replace(/%description%/g,  $('.hector68-map-input-widget-search-bar', this).val() );
});


$('.hector68-map-input-widget').on(
    'initializeAfter',
    function(event) {
        $(event.MapInputWidget.getSearchBar()).change(function(){
             event.MapInputWidget.setPosition(event.MapInputWidget.getMap().marker.getPosition())
        });

        event.MapInputWidget.getMap().addListener(
            'zoom_changed',
            function(){
                  event.MapInputWidget.setPosition(event.MapInputWidget.getMap().marker.getPosition())
                }
        );
    }
);
JAVASCRIPT;
echo $this->registerJs($js);